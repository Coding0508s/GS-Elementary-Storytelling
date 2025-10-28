<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\VideoSubmission;
use App\Models\AiEvaluation;
use App\Services\OpenAiService;
use Exception;

class BatchAiEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $submissionId;
    protected $adminId;

    /**
     * Create a new job instance.
     */
    public function __construct($submissionId, $adminId)
    {
        $this->submissionId = $submissionId;
        $this->adminId = $adminId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('일괄 AI 채점 작업 시작', [
                'submission_id' => $this->submissionId,
                'admin_id' => $this->adminId
            ]);

            // 영상 제출 정보 가져오기
            $submission = VideoSubmission::find($this->submissionId);
            if (!$submission) {
                Log::error('영상 제출 정보를 찾을 수 없습니다', ['submission_id' => $this->submissionId]);
                return;
            }

            // 영상 파일 존재 여부 확인
            if (!$this->checkVideoFileExists($submission)) {
                Log::warning('영상 파일이 존재하지 않습니다', [
                    'submission_id' => $this->submissionId,
                    'video_path' => $submission->video_file_path
                ]);
                
                // AI 평가 레코드를 실패 상태로 생성
                $aiEvaluation = AiEvaluation::where('video_submission_id', $this->submissionId)->first() ?? new AiEvaluation();
                $aiEvaluation->video_submission_id = $this->submissionId;
                $aiEvaluation->admin_id = $this->adminId;
                $aiEvaluation->processing_status = AiEvaluation::STATUS_FAILED;
                $aiEvaluation->error_message = '영상 파일이 존재하지 않습니다.';
                $aiEvaluation->save();
                
                return;
            }

            // 기존 AI 평가가 있는지 확인
            $existingEvaluation = AiEvaluation::where('video_submission_id', $this->submissionId)->first();
            if ($existingEvaluation && $existingEvaluation->isCompleted()) {
                Log::info('이미 완료된 AI 평가가 있습니다', ['submission_id' => $this->submissionId]);
                return;
            }

            // AI 평가 레코드 생성 또는 업데이트
            $aiEvaluation = $existingEvaluation ?? new AiEvaluation();
            $aiEvaluation->video_submission_id = $this->submissionId;
            $aiEvaluation->admin_id = $this->adminId;
            $aiEvaluation->processing_status = AiEvaluation::STATUS_PROCESSING;
            $aiEvaluation->error_message = null;
            $aiEvaluation->save();

            // OpenAI 서비스를 사용한 평가
            Log::info('OpenAI 서비스 초기화 및 평가 시작', [
                'submission_id' => $this->submissionId,
                'video_path' => $submission->video_file_path
            ]);
            
            $openAiService = new OpenAiService();
            $startTime = microtime(true);
            $result = $openAiService->evaluateVideo($submission->video_file_path);
            $endTime = microtime(true);
            
            Log::info('OpenAI 평가 완료', [
                'submission_id' => $this->submissionId,
                'processing_time' => round($endTime - $startTime, 2) . ' seconds'
            ]);

            // 결과 저장
            $aiEvaluation->update([
                'pronunciation_score' => $result['pronunciation_score'],
                'vocabulary_score' => $result['vocabulary_score'],
                'fluency_score' => $result['fluency_score'],
                'transcription' => $result['transcription'],
                'ai_feedback' => $result['ai_feedback'],
                'processing_status' => AiEvaluation::STATUS_COMPLETED,
                'processed_at' => now()
            ]);

            // 총점 계산
            $aiEvaluation->calculateTotalScore();
            $aiEvaluation->save();

            Log::info('일괄 AI 채점 완료', [
                'submission_id' => $this->submissionId,
                'total_score' => $aiEvaluation->total_score
            ]);

        } catch (Exception $e) {
            Log::error('일괄 AI 채점 실패', [
                'submission_id' => $this->submissionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 실패 상태로 업데이트
            if (isset($aiEvaluation)) {
                $aiEvaluation->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('일괄 AI 채점 작업 실패', [
            'submission_id' => $this->submissionId,
            'admin_id' => $this->adminId,
            'error' => $exception->getMessage()
        ]);

        // 실패 상태로 업데이트
        $aiEvaluation = AiEvaluation::where('video_submission_id', $this->submissionId)->first();
        if ($aiEvaluation) {
            $aiEvaluation->update([
                'processing_status' => AiEvaluation::STATUS_FAILED,
                'error_message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * 영상 파일 존재 여부 확인
     */
    private function checkVideoFileExists($submission)
    {
        try {
            // S3에 저장된 경우
            if ($submission->isStoredOnS3()) {
                return \Illuminate\Support\Facades\Storage::disk('s3')->exists($submission->video_file_path);
            }
            
            // 로컬에 저장된 경우 - public 디스크 사용
            if ($submission->isStoredLocally()) {
                return \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->video_file_path);
            }
            
            // 기본 스토리지에서 확인 (public 디스크)
            return \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->video_file_path);
            
        } catch (\Exception $e) {
            Log::error('영상 파일 존재 여부 확인 중 오류', [
                'submission_id' => $submission->id,
                'video_path' => $submission->video_file_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
