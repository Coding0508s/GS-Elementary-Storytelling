# AI 일괄 체점 시스템 구현 가이드

## 📋 개요
Laravel 기반 AI 일괄 체점 시스템으로, OpenAI Whisper와 GPT-4를 사용하여 영상 파일을 자동으로 평가하는 시스템입니다.

---

## 🏗️ 시스템 아키텍처

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   관리자 대시보드  │───▶│   Queue Worker   │───▶│   OpenAI API    │
│                 │    │                  │    │  (Whisper+GPT)  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Video Upload   │    │  BatchAiEvalJob  │    │  FFmpeg Audio   │
│                 │    │                  │    │   Extraction    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

---

## 📁 파일 구조

```
app/
├── Http/Controllers/
│   └── AdminController.php          # 관리자 컨트롤러
├── Jobs/
│   └── BatchAiEvaluationJob.php    # AI 평가 작업
├── Models/
│   ├── VideoSubmission.php         # 영상 제출 모델
│   └── AiEvaluation.php           # AI 평가 모델
└── Services/
    └── OpenAiService.php          # OpenAI 서비스

resources/views/admin/
└── batch-evaluation.blade.php     # 일괄 평가 관리 페이지

routes/
└── web.php                        # 라우트 정의
```

---

## 🔧 1. 데이터베이스 마이그레이션

### A. AI 평가 테이블 생성

```php
// database/migrations/xxxx_create_ai_evaluations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->integer('pronunciation_score')->nullable();
            $table->integer('fluency_score')->nullable();
            $table->integer('comprehension_score')->nullable();
            $table->text('pronunciation_feedback')->nullable();
            $table->text('fluency_feedback')->nullable();
            $table->text('comprehension_feedback')->nullable();
            $table->text('overall_feedback')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            
            $table->index(['video_submission_id', 'processing_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_evaluations');
    }
};
```

---

## 🎯 2. 모델 구현

### A. VideoSubmission 모델

```php
// app/Models/VideoSubmission.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoSubmission extends Model
{
    protected $fillable = [
        'institution_id',
        'student_name',
        'student_grade',
        'video_file_path',
        'submission_date',
        'status'
    ];

    protected $casts = [
        'submission_date' => 'datetime',
    ];

    // AI 평가 관계
    public function aiEvaluations()
    {
        return $this->hasMany(AiEvaluation::class);
    }

    // 최신 AI 평가
    public function latestAiEvaluation()
    {
        return $this->hasOne(AiEvaluation::class)->latest();
    }

    // S3 저장 여부 확인
    public function isStoredOnS3()
    {
        return str_starts_with($this->video_file_path, 'videos/');
    }

    // 영상 파일 URL 생성
    public function getVideoUrlAttribute()
    {
        if ($this->isStoredOnS3()) {
            return Storage::disk('s3')->url($this->video_file_path);
        }
        return Storage::disk('public')->url($this->video_file_path);
    }
}
```

### B. AiEvaluation 모델

```php
// app/Models/AiEvaluation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEvaluation extends Model
{
    protected $fillable = [
        'video_submission_id',
        'admin_id',
        'pronunciation_score',
        'fluency_score',
        'comprehension_score',
        'pronunciation_feedback',
        'fluency_feedback',
        'comprehension_feedback',
        'overall_feedback',
        'processing_status',
        'error_message',
        'raw_response'
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    // 상태 상수
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // 관계
    public function videoSubmission()
    {
        return $this->belongsTo(VideoSubmission::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // 총점 계산
    public function getTotalScoreAttribute()
    {
        if ($this->pronunciation_score && $this->fluency_score && $this->comprehension_score) {
            return round(($this->pronunciation_score + $this->fluency_score + $this->comprehension_score) / 3, 1);
        }
        return null;
    }

    // 처리 상태 확인
    public function isCompleted()
    {
        return $this->processing_status === self::STATUS_COMPLETED;
    }

    public function isProcessing()
    {
        return $this->processing_status === self::STATUS_PROCESSING;
    }

    public function isFailed()
    {
        return $this->processing_status === self::STATUS_FAILED;
    }
}
```

---

## 🤖 3. OpenAI 서비스 구현

### A. OpenAiService 클래스

```php
// app/Services/OpenAiService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService
{
    private $maxAudioSize = 25 * 1024 * 1024; // 25MB
    private $chunkSize = 20 * 1024 * 1024; // 20MB

    /**
     * 영상 평가 메인 메서드
     */
    public function evaluateVideo($videoFilePath)
    {
        try {
            Log::info('AI 영상 평가 시작', ['video_path' => $videoFilePath]);
            $totalStartTime = microtime(true);

            // 1단계: 영상에서 오디오 추출
            $extractStartTime = microtime(true);
            $audioFilePath = $this->extractAudioFromVideo($videoFilePath);
            $extractEndTime = microtime(true);
            
            Log::info('오디오 추출 완료', [
                'audio_path' => $audioFilePath,
                'extraction_time' => round($extractEndTime - $extractStartTime, 2) . ' seconds'
            ]);

            // 2단계: 음성을 텍스트로 변환
            $transcribeStartTime = microtime(true);
            $transcription = $this->transcribeAudio($audioFilePath);
            $transcribeEndTime = microtime(true);
            
            Log::info('음성 전사 완료', [
                'transcription_length' => strlen($transcription),
                'transcription_time' => round($transcribeEndTime - $transcribeStartTime, 2) . ' seconds'
            ]);

            // 3단계: GPT-4로 평가
            $evaluateStartTime = microtime(true);
            $evaluation = $this->evaluateTranscription($transcription);
            $evaluateEndTime = microtime(true);
            
            Log::info('GPT-4 평가 완료', [
                'evaluation_time' => round($evaluateEndTime - $evaluateStartTime, 2) . ' seconds'
            ]);

            // 4단계: 임시 오디오 파일 삭제
            $this->cleanupAudioFile($audioFilePath);

            $totalEndTime = microtime(true);
            Log::info('AI 영상 평가 완료', [
                'total_time' => round($totalEndTime - $totalStartTime, 2) . ' seconds',
                'evaluation' => $evaluation
            ]);

            return $evaluation;

        } catch (\Exception $e) {
            Log::error('AI 영상 평가 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 영상에서 오디오 추출
     */
    private function extractAudioFromVideo($videoFilePath)
    {
        $audioFileName = 'audio_' . uniqid() . '.wav';
        $audioFilePath = storage_path('app/temp/' . $audioFileName);

        // temp 디렉토리 생성
        if (!file_exists(dirname($audioFilePath))) {
            mkdir(dirname($audioFilePath), 0755, true);
        }

        // FFmpeg로 오디오 추출
        $command = sprintf(
            'ffmpeg -i "%s" -vn -acodec pcm_s16le -ar 16000 -ac 1 "%s" 2>&1',
            $videoFilePath,
            $audioFilePath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('오디오 추출 실패: ' . implode("\n", $output));
        }

        return $audioFilePath;
    }

    /**
     * 오디오를 텍스트로 변환 (Whisper)
     */
    private function transcribeAudio($audioFilePath)
    {
        // 파일 크기 확인
        $fileSize = filesize($audioFilePath);
        if ($fileSize > $this->maxAudioSize) {
            return $this->transcribeLargeAudio($audioFilePath);
        }

        // 일반 크기 파일 처리
        $response = OpenAI::audio()->transcriptions()->create([
            'model' => 'whisper-1',
            'file' => fopen($audioFilePath, 'r'),
            'response_format' => 'text',
        ]);

        return $response;
    }

    /**
     * 큰 오디오 파일 처리 (청크 단위)
     */
    private function transcribeLargeAudio($audioFilePath)
    {
        $fileSize = filesize($audioFilePath);
        $chunks = ceil($fileSize / $this->chunkSize);
        $transcriptions = [];

        for ($i = 0; $i < $chunks; $i++) {
            $start = $i * $this->chunkSize;
            $length = min($this->chunkSize, $fileSize - $start);
            
            $chunkData = file_get_contents($audioFilePath, false, null, $start, $length);
            $chunkFile = storage_path('app/temp/chunk_' . $i . '.wav');
            file_put_contents($chunkFile, $chunkData);

            try {
                $response = OpenAI::audio()->transcriptions()->create([
                    'model' => 'whisper-1',
                    'file' => fopen($chunkFile, 'r'),
                    'response_format' => 'text',
                ]);
                
                $transcriptions[] = $response;
                unlink($chunkFile);
            } catch (\Exception $e) {
                Log::warning("청크 {$i} 전사 실패: " . $e->getMessage());
            }
        }

        return implode(' ', $transcriptions);
    }

    /**
     * 텍스트를 GPT-4로 평가
     */
    private function evaluateTranscription($transcription)
    {
        $prompt = $this->buildEvaluationPrompt($transcription);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '당신은 영어 발음 평가 전문가입니다. 주어진 텍스트를 발음, 유창성, 이해도 3가지 항목으로 평가해주세요.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        $evaluationText = $response->choices[0]->message->content;
        return $this->parseEvaluationResponse($evaluationText);
    }

    /**
     * 평가 프롬프트 생성
     */
    private function buildEvaluationPrompt($transcription)
    {
        return "다음은 학생의 영어 발음을 전사한 텍스트입니다. 각 항목을 1-10점으로 평가하고 구체적인 피드백을 제공해주세요.

전사된 텍스트: \"{$transcription}\"

평가 기준:
1. 발음 (Pronunciation): 단어의 정확한 발음
2. 유창성 (Fluency): 말의 흐름과 속도
3. 이해도 (Comprehension): 내용의 이해도와 전달력

다음 JSON 형식으로 응답해주세요:
{
    \"pronunciation_score\": 점수,
    \"fluency_score\": 점수,
    \"comprehension_score\": 점수,
    \"pronunciation_feedback\": \"구체적인 피드백\",
    \"fluency_feedback\": \"구체적인 피드백\",
    \"comprehension_feedback\": \"구체적인 피드백\",
    \"overall_feedback\": \"전체적인 피드백\"
}";
    }

    /**
     * GPT-4 응답 파싱 (4단계 전략)
     */
    private function parseEvaluationResponse($response)
    {
        try {
            Log::info('AI 응답 파싱 시작', ['response_length' => strlen($response)]);

            // 방법 1: 전체 응답이 JSON인 경우
            $evaluation = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                Log::info('전체 JSON 파싱 성공');
                return $this->formatEvaluation($evaluation);
            }

            // 방법 2: JSON이 텍스트에 포함된 경우 (마크다운 코드 블록 등)
            $cleanedResponse = preg_replace('/```json\s*|\s*```/', '', $response);
            $evaluation = json_decode($cleanedResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                Log::info('마크다운 제거 후 JSON 파싱 성공');
                return $this->formatEvaluation($evaluation);
            }

            // 방법 3: JSON 부분만 추출
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $evaluation = json_decode($jsonString, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                    Log::info('부분 JSON 추출 후 파싱 성공');
                    return $this->formatEvaluation($evaluation);
                }
            }

            // 방법 4: 정규식으로 점수와 피드백 추출 시도
            $scores = [];
            if (preg_match('/"pronunciation_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['pronunciation_score'] = (int)$matches[1];
            }
            if (preg_match('/"fluency_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['fluency_score'] = (int)$matches[1];
            }
            if (preg_match('/"comprehension_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['comprehension_score'] = (int)$matches[1];
            }

            if (count($scores) >= 3) {
                Log::info('정규식으로 점수 추출 성공', ['scores' => $scores]);
                return $this->formatEvaluation($scores);
            }

            Log::error('AI 응답 파싱 실패', [
                'response' => $response,
                'json_error' => json_last_error_msg()
            ]);

            // 기본값 반환
            return $this->getDefaultEvaluation();

        } catch (\Exception $e) {
            Log::error('AI 응답 파싱 중 오류: ' . $e->getMessage());
            return $this->getDefaultEvaluation();
        }
    }

    /**
     * 평가 결과 포맷팅
     */
    private function formatEvaluation($evaluation)
    {
        return [
            'pronunciation_score' => (int)($evaluation['pronunciation_score'] ?? 5),
            'fluency_score' => (int)($evaluation['fluency_score'] ?? 5),
            'comprehension_score' => (int)($evaluation['comprehension_score'] ?? 5),
            'pronunciation_feedback' => $evaluation['pronunciation_feedback'] ?? '평가를 완료할 수 없습니다.',
            'fluency_feedback' => $evaluation['fluency_feedback'] ?? '평가를 완료할 수 없습니다.',
            'comprehension_feedback' => $evaluation['comprehension_feedback'] ?? '평가를 완료할 수 없습니다.',
            'overall_feedback' => $evaluation['overall_feedback'] ?? '전체적인 평가를 완료할 수 없습니다.',
            'raw_response' => $evaluation
        ];
    }

    /**
     * 기본 평가 결과
     */
    private function getDefaultEvaluation()
    {
        return [
            'pronunciation_score' => 5,
            'fluency_score' => 5,
            'comprehension_score' => 5,
            'pronunciation_feedback' => 'AI 평가 중 오류가 발생했습니다.',
            'fluency_feedback' => 'AI 평가 중 오류가 발생했습니다.',
            'comprehension_feedback' => 'AI 평가 중 오류가 발생했습니다.',
            'overall_feedback' => 'AI 평가 중 오류가 발생했습니다. 다시 시도해주세요.',
            'raw_response' => null
        ];
    }

    /**
     * 임시 오디오 파일 정리
     */
    private function cleanupAudioFile($audioFilePath)
    {
        if (file_exists($audioFilePath)) {
            unlink($audioFilePath);
        }
    }
}
```

---

## 🚀 4. Queue Job 구현

### A. BatchAiEvaluationJob 클래스

```php
// app/Jobs/BatchAiEvaluationJob.php
<?php

namespace App\Jobs;

use App\Models\VideoSubmission;
use App\Models\AiEvaluation;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BatchAiEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10분
    public $tries = 3;

    protected $submissionId;
    protected $adminId;

    public function __construct($submissionId, $adminId = null)
    {
        $this->submissionId = $submissionId;
        $this->adminId = $adminId;
    }

    public function handle()
    {
        try {
            $submission = VideoSubmission::findOrFail($this->submissionId);
            
            Log::info('AI 평가 작업 시작', [
                'submission_id' => $this->submissionId,
                'video_path' => $submission->video_file_path
            ]);

            // 기존 평가가 있는지 확인
            $existingEvaluation = AiEvaluation::where('video_submission_id', $this->submissionId)
                ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                ->first();

            if ($existingEvaluation) {
                Log::info('이미 완료된 평가가 존재합니다', [
                    'submission_id' => $this->submissionId,
                    'evaluation_id' => $existingEvaluation->id
                ]);
                return;
            }

            // AI 평가 생성 (처리 중 상태)
            $evaluation = AiEvaluation::create([
                'video_submission_id' => $this->submissionId,
                'admin_id' => $this->adminId,
                'processing_status' => AiEvaluation::STATUS_PROCESSING,
            ]);

            // 영상 파일 경로 확인
            $videoPath = $submission->video_file_path;
            if (!$videoPath) {
                throw new \Exception('영상 파일 경로가 없습니다.');
            }

            // 파일 존재 확인
            if ($submission->isStoredOnS3()) {
                if (!Storage::disk('s3')->exists($videoPath)) {
                    throw new \Exception('S3에 영상 파일이 존재하지 않습니다.');
                }
                $fullVideoPath = Storage::disk('s3')->path($videoPath);
            } else {
                if (!Storage::disk('public')->exists($videoPath)) {
                    throw new \Exception('로컬에 영상 파일이 존재하지 않습니다.');
                }
                $fullVideoPath = Storage::disk('public')->path($videoPath);
            }

            Log::info('OpenAI 서비스 초기화 및 평가 시작', [
                'submission_id' => $this->submissionId,
                'video_path' => $submission->video_file_path
            ]);

            // OpenAI 서비스로 평가 실행
            $openAiService = new OpenAiService();
            $startTime = microtime(true);
            $result = $openAiService->evaluateVideo($fullVideoPath);
            $endTime = microtime(true);

            Log::info('OpenAI 평가 완료', [
                'submission_id' => $this->submissionId,
                'processing_time' => round($endTime - $startTime, 2) . ' seconds'
            ]);

            // 평가 결과 저장
            $evaluation->update([
                'pronunciation_score' => $result['pronunciation_score'],
                'fluency_score' => $result['fluency_score'],
                'comprehension_score' => $result['comprehension_score'],
                'pronunciation_feedback' => $result['pronunciation_feedback'],
                'fluency_feedback' => $result['fluency_feedback'],
                'comprehension_feedback' => $result['comprehension_feedback'],
                'overall_feedback' => $result['overall_feedback'],
                'raw_response' => $result['raw_response'],
                'processing_status' => AiEvaluation::STATUS_COMPLETED,
            ]);

            Log::info('AI 평가 작업 완료', [
                'submission_id' => $this->submissionId,
                'evaluation_id' => $evaluation->id,
                'total_score' => $evaluation->total_score
            ]);

        } catch (\Exception $e) {
            Log::error('AI 평가 작업 실패', [
                'submission_id' => $this->submissionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 실패 상태로 업데이트
            AiEvaluation::where('video_submission_id', $this->submissionId)
                ->where('processing_status', AiEvaluation::STATUS_PROCESSING)
                ->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => $e->getMessage()
                ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('AI 평가 작업 완전 실패', [
            'submission_id' => $this->submissionId,
            'error' => $exception->getMessage()
        ]);

        AiEvaluation::where('video_submission_id', $this->submissionId)
            ->where('processing_status', AiEvaluation::STATUS_PROCESSING)
            ->update([
                'processing_status' => AiEvaluation::STATUS_FAILED,
                'error_message' => '작업이 완전히 실패했습니다: ' . $exception->getMessage()
            ]);
    }
}
```

---

## 🎛️ 5. 관리자 컨트롤러 구현

### A. AdminController 메서드들

```php
// app/Http/Controllers/AdminController.php

/**
 * AI 일괄 평가 목록
 */
public function batchEvaluationList(Request $request)
{
    $admin = Auth::guard('admin')->user();
    
    if (!$admin || !in_array($admin->role, ['admin', 'super_admin'])) {
        return redirect()->route('admin.login')->with('error', '접근 권한이 없습니다.');
    }

    // 통계 계산
    $totalSubmissions = VideoSubmission::count();
    $completedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
        $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
    })->count();
    $processingEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
        $query->where('processing_status', AiEvaluation::STATUS_PROCESSING);
    })->count();
    $failedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
        $query->where('processing_status', AiEvaluation::STATUS_FAILED);
    })->count();
    $pendingSubmissions = VideoSubmission::whereDoesntHave('aiEvaluations')->count();

    // 영상 목록
    $submissions = VideoSubmission::with(['aiEvaluations', 'institution'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('admin.batch-evaluation', compact(
        'totalSubmissions',
        'completedEvaluations',
        'processingEvaluations',
        'failedEvaluations',
        'pendingSubmissions',
        'submissions'
    ));
}

/**
 * AI 일괄 평가 시작
 */
public function startBatchAiEvaluation(Request $request)
{
    $admin = Auth::guard('admin')->user();
    
    if (!$admin || !in_array($admin->role, ['admin', 'super_admin'])) {
        return response()->json(['success' => false, 'message' => '접근 권한이 없습니다.'], 403);
    }

    try {
        // 평가할 영상들 가져오기
        $submissions = VideoSubmission::whereDoesntHave('aiEvaluations', function($query) {
            $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
        })->get();

        if ($submissions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '평가할 영상이 없습니다.'
            ]);
        }

        $queuedCount = 0;
        foreach ($submissions as $submission) {
            // 기존 처리 중인 평가가 있는지 확인
            $existingProcessing = AiEvaluation::where('video_submission_id', $submission->id)
                ->where('processing_status', AiEvaluation::STATUS_PROCESSING)
                ->exists();

            if (!$existingProcessing) {
                // 파일 존재 확인
                if ($submission->isStoredOnS3()) {
                    if (!Storage::disk('s3')->exists($submission->video_file_path)) {
                        Log::warning('S3 파일이 존재하지 않음', [
                            'submission_id' => $submission->id,
                            'video_path' => $submission->video_file_path
                        ]);
                        continue;
                    }
                } else {
                    if (!Storage::disk('public')->exists($submission->video_file_path)) {
                        Log::warning('로컬 파일이 존재하지 않음', [
                            'submission_id' => $submission->id,
                            'video_path' => $submission->video_file_path
                        ]);
                        continue;
                    }
                }

                // 작업 큐에 추가
                BatchAiEvaluationJob::dispatch($submission->id, $admin->id);
                $queuedCount++;
            }
        }

        Log::info('AI 일괄 평가 시작', [
            'admin_id' => $admin->id,
            'total_submissions' => $submissions->count(),
            'queued_count' => $queuedCount
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$queuedCount}개의 영상이 평가 큐에 추가되었습니다.",
            'data' => [
                'queued_count' => $queuedCount,
                'total_submissions' => $submissions->count()
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('AI 일괄 평가 시작 실패: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'AI 일괄 평가 시작에 실패했습니다: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * AI 일괄 평가 취소
 */
public function cancelBatchAiEvaluation(Request $request)
{
    $admin = Auth::guard('admin')->user();
    
    if (!$admin || !in_array($admin->role, ['admin', 'super_admin'])) {
        return response()->json(['success' => false, 'message' => '접근 권한이 없습니다.'], 403);
    }

    try {
        // 처리 중인 평가들을 실패 상태로 변경
        $processingEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_PROCESSING)->get();
        $cancelledCount = 0;

        foreach ($processingEvaluations as $evaluation) {
            $evaluation->update([
                'processing_status' => AiEvaluation::STATUS_FAILED,
                'error_message' => '관리자에 의해 취소되었습니다.'
            ]);
            $cancelledCount++;
        }

        // 큐 클리어
        Artisan::call('queue:clear', ['--queue' => 'default']);

        Log::info('AI 일괄 평가 취소', [
            'admin_id' => $admin->id,
            'cancelled_count' => $cancelledCount
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$cancelledCount}개의 평가가 취소되었습니다.",
            'data' => [
                'cancelled_count' => $cancelledCount
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('AI 일괄 평가 취소 실패: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'AI 일괄 평가 취소에 실패했습니다: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * AI 평가 진행 상황 조회
 */
public function getBatchAiEvaluationProgress(Request $request)
{
    $admin = Auth::guard('admin')->user();
    
    if (!$admin || !in_array($admin->role, ['admin', 'super_admin'])) {
        return response()->json(['success' => false, 'message' => '접근 권한이 없습니다.'], 403);
    }

    try {
        $totalSubmissions = VideoSubmission::count();
        $completedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
            $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
        })->count();
        $processingEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
            $query->where('processing_status', AiEvaluation::STATUS_PROCESSING);
        })->count();
        $noFileEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
            $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                  ->where('error_message', '영상 파일이 존재하지 않습니다.');
        })->count();
        $failedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
            $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                  ->where(function($q) {
                      $q->where('error_message', '!=', '영상 파일이 존재하지 않습니다.')
                        ->orWhereNull('error_message');
                  });
        })->count();
        $pendingSubmissions = VideoSubmission::whereDoesntHave('aiEvaluations')->count();

        $progressPercentage = $totalSubmissions > 0 ? round(($completedEvaluations / $totalSubmissions) * 100, 1) : 0;

        // 최근 평가 결과
        $recentEvaluations = AiEvaluation::with('videoSubmission')
            ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_submissions' => $totalSubmissions,
                'completed_evaluations' => $completedEvaluations,
                'processing_evaluations' => $processingEvaluations,
                'failed_evaluations' => $failedEvaluations,
                'no_file_evaluations' => $noFileEvaluations,
                'pending_submissions' => $pendingSubmissions,
                'progress_percentage' => $progressPercentage,
                'recent_evaluations' => $recentEvaluations->map(function($evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'student_name' => $evaluation->videoSubmission->student_name,
                        'total_score' => $evaluation->total_score,
                        'pronunciation_score' => $evaluation->pronunciation_score,
                        'fluency_score' => $evaluation->fluency_score,
                        'comprehension_score' => $evaluation->comprehension_score,
                        'completed_at' => $evaluation->updated_at->format('Y-m-d H:i:s')
                    ];
                })
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('AI 평가 진행 상황 조회 실패: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => '진행 상황 조회에 실패했습니다.'
        ], 500);
    }
}
```

---

## 🛣️ 6. 라우트 설정

```php
// routes/web.php

// 관리자 인증이 필요한 라우트들
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // AI 일괄 평가 관련
        Route::get('/batch-evaluation', [AdminController::class, 'batchEvaluationList'])
            ->name('batch.evaluation.list');
        
        Route::post('/batch-ai-evaluation/start', [AdminController::class, 'startBatchAiEvaluation'])
            ->name('batch.ai.evaluation.start');
        
        Route::post('/batch-ai-evaluation/cancel', [AdminController::class, 'cancelBatchAiEvaluation'])
            ->name('batch.ai.evaluation.cancel');
        
        Route::get('/batch-ai-evaluation/progress', [AdminController::class, 'getBatchAiEvaluationProgress'])
            ->name('batch.ai.evaluation.progress');
    });
});
```

---

## 🎨 7. 프론트엔드 구현

### A. 관리자 페이지 템플릿

```html
<!-- resources/views/admin/batch-evaluation.blade.php -->
@extends('admin.layout')

@section('title', 'AI 일괄 채점')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-robot"></i> AI 일괄 채점 관리
                    </h3>
                </div>
                <div class="card-body">
                    <!-- 통계 카드 -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-video"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">총 접수 영상</span>
                                    <span class="info-box-number" id="total-submissions">{{ $totalSubmissions }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">채점 완료</span>
                                    <span class="info-box-number" id="completed-evaluations">{{ $completedEvaluations }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">채점 중</span>
                                    <span class="info-box-number" id="processing-evaluations">{{ $processingEvaluations }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-times"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">채점 실패</span>
                                    <span class="info-box-number" id="failed-evaluations">{{ $failedEvaluations }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary">
                                    <i class="fas fa-hourglass-half"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">대기 중</span>
                                    <span class="info-box-number" id="pending-submissions">{{ $pendingSubmissions }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-percentage"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">진행률</span>
                                    <span class="info-box-number" id="progress-percentage">
                                        {{ $totalSubmissions > 0 ? round(($completedEvaluations / $totalSubmissions) * 100, 1) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 진행률 바 -->
                    <div class="progress mb-4">
                        <div class="progress-bar" id="progress-bar" role="progressbar" 
                             style="width: {{ $totalSubmissions > 0 ? round(($completedEvaluations / $totalSubmissions) * 100, 1) : 0 }}%">
                        </div>
                    </div>

                    <!-- 컨트롤 버튼 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <button id="start-batch-evaluation" class="btn btn-primary">
                                <i class="fas fa-play"></i> AI 일괄 채점 시작
                            </button>
                            <button id="cancel-batch-evaluation" class="btn btn-danger" style="display: none;">
                                <i class="fas fa-stop"></i> AI 일괄 채점 취소
                            </button>
                            <button id="refresh-progress" class="btn btn-info">
                                <i class="fas fa-sync"></i> 새로고침
                            </button>
                        </div>
                    </div>

                    <!-- 영상 목록 테이블 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>학생명</th>
                                    <th>기관</th>
                                    <th>제출일</th>
                                    <th>AI 채점 상태</th>
                                    <th>총점</th>
                                    <th>발음</th>
                                    <th>유창성</th>
                                    <th>이해도</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($submissions as $submission)
                                <tr data-submission-id="{{ $submission->id }}">
                                    <td>{{ $submission->id }}</td>
                                    <td>{{ $submission->student_name }}</td>
                                    <td>{{ $submission->institution->name ?? 'N/A' }}</td>
                                    <td>{{ $submission->submission_date->format('Y-m-d H:i') }}</td>
                                    <td id="status-{{ $submission->id }}">
                                        @if($submission->aiEvaluations->isNotEmpty())
                                            @php $latestEvaluation = $submission->aiEvaluations->first(); @endphp
                                            @if($latestEvaluation->processing_status === 'completed')
                                                <span class="badge badge-success">완료</span>
                                            @elseif($latestEvaluation->processing_status === 'processing')
                                                <span class="badge badge-warning">처리중</span>
                                            @elseif($latestEvaluation->processing_status === 'failed')
                                                <span class="badge badge-danger">실패</span>
                                            @else
                                                <span class="badge badge-secondary">대기</span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">미처리</span>
                                        @endif
                                    </td>
                                    <td id="total-score-{{ $submission->id }}">
                                        @if($submission->aiEvaluations->isNotEmpty() && $submission->aiEvaluations->first()->processing_status === 'completed')
                                            {{ $submission->aiEvaluations->first()->total_score }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td id="pronunciation-{{ $submission->id }}">
                                        @if($submission->aiEvaluations->isNotEmpty() && $submission->aiEvaluations->first()->processing_status === 'completed')
                                            {{ $submission->aiEvaluations->first()->pronunciation_score }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td id="fluency-{{ $submission->id }}">
                                        @if($submission->aiEvaluations->isNotEmpty() && $submission->aiEvaluations->first()->processing_status === 'completed')
                                            {{ $submission->aiEvaluations->first()->fluency_score }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td id="comprehension-{{ $submission->id }}">
                                        @if($submission->aiEvaluations->isNotEmpty() && $submission->aiEvaluations->first()->processing_status === 'completed')
                                            {{ $submission->aiEvaluations->first()->comprehension_score }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission->aiEvaluations->isNotEmpty() && $submission->aiEvaluations->first()->processing_status === 'completed')
                                            <button class="btn btn-sm btn-info view-evaluation" data-id="{{ $submission->aiEvaluations->first()->id }}">
                                                <i class="fas fa-eye"></i> 보기
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-primary start-single-evaluation" data-id="{{ $submission->id }}">
                                                <i class="fas fa-play"></i> 채점
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이지네이션 -->
                    {{ $submissions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let isEvaluationRunning = false;
    let refreshInterval;

    // AI 일괄 채점 시작
    $('#start-batch-evaluation').click(function() {
        if (isEvaluationRunning) return;

        if (!confirm('AI 일괄 채점을 시작하시겠습니까?')) return;

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 처리 중...');

        $.ajax({
            url: '{{ route("admin.batch.ai.evaluation.start") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#start-batch-evaluation').hide();
                    $('#cancel-batch-evaluation').show();
                    isEvaluationRunning = true;
                    startProgressRefresh();
                } else {
                    alert('오류: ' + response.message);
                    $('#start-batch-evaluation').prop('disabled', false).html('<i class="fas fa-play"></i> AI 일괄 채점 시작');
                }
            },
            error: function(xhr) {
                alert('네트워크 오류가 발생했습니다.');
                $('#start-batch-evaluation').prop('disabled', false).html('<i class="fas fa-play"></i> AI 일괄 채점 시작');
            }
        });
    });

    // AI 일괄 채점 취소
    $('#cancel-batch-evaluation').click(function() {
        if (!confirm('AI 일괄 채점을 취소하시겠습니까?')) return;

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 취소 중...');

        $.ajax({
            url: '{{ route("admin.batch.ai.evaluation.cancel") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#cancel-batch-evaluation').hide();
                    $('#start-batch-evaluation').show();
                    isEvaluationRunning = false;
                    stopProgressRefresh();
                    refreshProgress();
                } else {
                    alert('오류: ' + response.message);
                    $('#cancel-batch-evaluation').prop('disabled', false).html('<i class="fas fa-stop"></i> AI 일괄 채점 취소');
                }
            },
            error: function(xhr) {
                alert('네트워크 오류가 발생했습니다.');
                $('#cancel-batch-evaluation').prop('disabled', false).html('<i class="fas fa-stop"></i> AI 일괄 채점 취소');
            }
        });
    });

    // 새로고침 버튼
    $('#refresh-progress').click(function() {
        refreshProgress();
    });

    // 진행 상황 새로고침
    function refreshProgress() {
        $.ajax({
            url: '{{ route("admin.batch.ai.evaluation.progress") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatistics(response.data);
                    updateTable(response.data);
                }
            },
            error: function(xhr) {
                console.error('진행 상황 조회 실패');
            }
        });
    }

    // 통계 업데이트
    function updateStatistics(data) {
        $('#total-submissions').text(data.total_submissions);
        $('#completed-evaluations').text(data.completed_evaluations);
        $('#processing-evaluations').text(data.processing_evaluations);
        $('#failed-evaluations').text(data.failed_evaluations);
        $('#pending-submissions').text(data.pending_submissions);
        $('#progress-percentage').text(data.progress_percentage + '%');
        
        // 진행률 바 업데이트
        $('#progress-bar').css('width', data.progress_percentage + '%');
        
        // 버튼 상태 업데이트
        if (data.processing_evaluations > 0) {
            $('#start-batch-evaluation').hide();
            $('#cancel-batch-evaluation').show();
            isEvaluationRunning = true;
        } else {
            $('#start-batch-evaluation').show();
            $('#cancel-batch-evaluation').hide();
            isEvaluationRunning = false;
        }
    }

    // 테이블 업데이트
    function updateTable(data) {
        // 최근 평가 결과로 테이블 업데이트
        data.recent_evaluations.forEach(function(evaluation) {
            const row = $(`tr[data-submission-id="${evaluation.submission_id}"]`);
            if (row.length) {
                row.find(`#status-${evaluation.submission_id}`).html('<span class="badge badge-success">완료</span>');
                row.find(`#total-score-${evaluation.submission_id}`).text(evaluation.total_score);
                row.find(`#pronunciation-${evaluation.submission_id}`).text(evaluation.pronunciation_score);
                row.find(`#fluency-${evaluation.submission_id}`).text(evaluation.fluency_score);
                row.find(`#comprehension-${evaluation.submission_id}`).text(evaluation.comprehension_score);
            }
        });
    }

    // 자동 새로고침 시작
    function startProgressRefresh() {
        refreshInterval = setInterval(refreshProgress, 5000); // 5초마다
    }

    // 자동 새로고침 중지
    function stopProgressRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    // 개별 채점 시작
    $(document).on('click', '.start-single-evaluation', function() {
        const submissionId = $(this).data('id');
        
        if (!confirm('이 영상을 AI로 채점하시겠습니까?')) return;

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 처리 중...');

        $.ajax({
            url: '{{ route("admin.batch.ai.evaluation.start") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                submission_ids: [submissionId]
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    refreshProgress();
                } else {
                    alert('오류: ' + response.message);
                    $(`.start-single-evaluation[data-id="${submissionId}"]`).prop('disabled', false).html('<i class="fas fa-play"></i> 채점');
                }
            },
            error: function(xhr) {
                alert('네트워크 오류가 발생했습니다.');
                $(`.start-single-evaluation[data-id="${submissionId}"]`).prop('disabled', false).html('<i class="fas fa-play"></i> 채점');
            }
        });
    });

    // 평가 결과 보기
    $(document).on('click', '.view-evaluation', function() {
        const evaluationId = $(this).data('id');
        // 평가 결과 모달 또는 페이지로 이동
        window.open(`/admin/ai-evaluation/${evaluationId}`, '_blank');
    });

    // 초기 로드 시 진행 상황 확인
    refreshProgress();
});
</script>
@endpush
```

---

## ⚙️ 8. 환경 설정

### A. .env 파일 설정

```env
# OpenAI 설정
OPENAI_API_KEY=your_openai_api_key_here

# Queue 설정
QUEUE_CONNECTION=database

# 세션 설정
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_SECURE_COOKIE=false

# 파일 저장소 설정
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=ap-northeast-2
AWS_BUCKET=your_bucket_name
```

### B. Composer 의존성

```json
{
    "require": {
        "openai-php/laravel": "^0.6.0",
        "intervention/image": "^2.7"
    }
}
```

---

## 🚀 9. 배포 및 실행

### A. 마이그레이션 실행

```bash
php artisan migrate
```

### B. Queue Worker 설정

```bash
# Supervisor 설정 파일
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --verbose --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

### C. Queue Worker 시작

```bash
php artisan queue:work --verbose --tries=3 --timeout=600
```

---

## 📊 10. 모니터링 및 로그

### A. 로그 확인

```bash
# Laravel 로그
tail -f storage/logs/laravel.log

# Queue Worker 로그
tail -f storage/logs/worker.log
```

### B. Queue 상태 확인

```bash
# 큐 작업 목록
php artisan queue:monitor

# 실패한 작업 재시도
php artisan queue:retry all
```

---

## 🔧 11. 문제 해결

### A. 일반적인 문제들

1. **FFmpeg 설치 필요**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg
```

2. **OpenAI API 키 설정**
```bash
php artisan config:clear
```

3. **Queue Worker 재시작**
```bash
php artisan queue:restart
```

4. **권한 문제**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### B. 성능 최적화

1. **큐 Worker 수 조정**
2. **타임아웃 설정 조정**
3. **메모리 제한 설정**
4. **병렬 처리 설정**

---

## 📈 12. 확장 가능성

### A. 추가 기능

1. **배치 크기 조정**
2. **우선순위 큐**
3. **이메일 알림**
4. **진행률 웹소켓**
5. **결과 내보내기**

### B. 모니터링 대시보드

1. **실시간 통계**
2. **성능 메트릭**
3. **오류 추적**
4. **사용량 분석**

---

이 가이드를 따라하면 Laravel 프로젝트에 완전한 AI 일괄 체점 시스템을 구축할 수 있습니다! 🎉
