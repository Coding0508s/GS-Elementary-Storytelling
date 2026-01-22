#!/bin/bash

# AI 일괄 체점 시스템 패키지 생성 스크립트
# 이 스크립트는 AI 일괄 체점 관련 파일들만 별도로 압축하여 다운로드 가능한 패키지를 만듭니다.

echo "🚀 AI 일괄 체점 시스템 패키지 생성 시작..."

# 패키지 디렉토리 생성
PACKAGE_DIR="ai-evaluation-package"
mkdir -p "$PACKAGE_DIR"

echo "📁 패키지 디렉토리 생성: $PACKAGE_DIR"

# 1. 앱 관련 파일들 복사
echo "📋 앱 관련 파일들 복사 중..."

# Jobs 디렉토리
mkdir -p "$PACKAGE_DIR/app/Jobs"
cp app/Jobs/BatchAiEvaluationJob.php "$PACKAGE_DIR/app/Jobs/"

# Services 디렉토리
mkdir -p "$PACKAGE_DIR/app/Services"
cp app/Services/OpenAiService.php "$PACKAGE_DIR/app/Services/"

# Models 디렉토리
mkdir -p "$PACKAGE_DIR/app/Models"
cp app/Models/AiEvaluation.php "$PACKAGE_DIR/app/Models/"

# Controllers 디렉토리 (AI 평가 관련 메서드만 포함)
mkdir -p "$PACKAGE_DIR/app/Http/Controllers"
# AdminController에서 AI 평가 관련 메서드들만 추출하여 별도 파일로 생성
cat > "$PACKAGE_DIR/app/Http/Controllers/AiEvaluationController.php" << 'EOF'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use App\Models\VideoSubmission;
use App\Models\AiEvaluation;
use App\Jobs\BatchAiEvaluationJob;

class AiEvaluationController extends Controller
{
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
}
EOF

# 2. 뷰 파일들 복사
echo "🎨 뷰 파일들 복사 중..."
mkdir -p "$PACKAGE_DIR/resources/views/admin"
cp resources/views/admin/batch-evaluation.blade.php "$PACKAGE_DIR/resources/views/admin/"

# 3. 마이그레이션 파일들 복사
echo "🗄️ 마이그레이션 파일들 복사 중..."
mkdir -p "$PACKAGE_DIR/database/migrations"
# AI 평가 관련 마이그레이션 찾기
find database/migrations -name "*ai_evaluation*" -o -name "*video_submission*" | while read file; do
    cp "$file" "$PACKAGE_DIR/database/migrations/"
done

# 4. 라우트 파일 생성
echo "🛣️ 라우트 파일 생성 중..."
cat > "$PACKAGE_DIR/routes/ai-evaluation.php" << 'EOF'
<?php

use App\Http\Controllers\AiEvaluationController;

// AI 일괄 평가 관련 라우트
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // AI 일괄 평가 관련
        Route::get('/batch-evaluation', [AiEvaluationController::class, 'batchEvaluationList'])
            ->name('batch.evaluation.list');
        
        Route::post('/batch-ai-evaluation/start', [AiEvaluationController::class, 'startBatchAiEvaluation'])
            ->name('batch.ai.evaluation.start');
        
        Route::post('/batch-ai-evaluation/cancel', [AiEvaluationController::class, 'cancelBatchAiEvaluation'])
            ->name('batch.ai.evaluation.cancel');
        
        Route::get('/batch-ai-evaluation/progress', [AiEvaluationController::class, 'getBatchAiEvaluationProgress'])
            ->name('batch.ai.evaluation.progress');
    });
});
EOF

# 5. 설정 파일들 복사
echo "⚙️ 설정 파일들 복사 중..."
mkdir -p "$PACKAGE_DIR/config"
cp config/services.php "$PACKAGE_DIR/config/"
cp config/queue.php "$PACKAGE_DIR/config/"

# 6. 환경 설정 파일 생성
echo "🔧 환경 설정 파일 생성 중..."
cat > "$PACKAGE_DIR/.env.example" << 'EOF'
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
EOF

# 7. Composer 의존성 파일 생성
echo "📦 Composer 의존성 파일 생성 중..."
cat > "$PACKAGE_DIR/composer.json" << 'EOF'
{
    "name": "ai-evaluation-system",
    "description": "AI 일괄 체점 시스템",
    "type": "project",
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "openai-php/laravel": "^0.6.0",
        "intervention/image": "^2.7",
        "guzzlehttp/guzzle": "^7.2"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
EOF

# 8. 설치 가이드 생성
echo "📖 설치 가이드 생성 중..."
cat > "$PACKAGE_DIR/INSTALLATION.md" << 'EOF'
# AI 일괄 체점 시스템 설치 가이드

## 📋 필수 요구사항

- PHP 8.1 이상
- Laravel 10.0 이상
- FFmpeg (오디오 추출용)
- MySQL/PostgreSQL/SQLite
- OpenAI API 키

## 🚀 설치 단계

### 1. 의존성 설치
```bash
composer install
```

### 2. 환경 설정
```bash
cp .env.example .env
# .env 파일에서 OpenAI API 키 설정
```

### 3. 데이터베이스 설정
```bash
php artisan migrate
```

### 4. 라우트 등록
`routes/web.php`에 다음 라인 추가:
```php
require_once __DIR__ . '/ai-evaluation.php';
```

### 5. Queue Worker 설정
```bash
php artisan queue:work --verbose --tries=3 --timeout=600
```

### 6. FFmpeg 설치
```bash
# Ubuntu/Debian
sudo apt update && sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg

# macOS
brew install ffmpeg
```

## 🔧 사용법

1. 관리자 페이지에서 "영상 일괄 채점" 메뉴 접근
2. "일괄 AI 채점 시작" 버튼 클릭
3. 실시간으로 진행 상황 확인
4. 필요시 "일괄 AI 채점 취소" 버튼으로 중단

## 📊 기능

- OpenAI Whisper를 사용한 음성 인식
- GPT-4를 사용한 영어 발표 평가
- 대용량 파일 분할 처리
- 실시간 진행 상황 모니터링
- 실패한 평가 재시도 기능
- 관리자 권한 기반 접근 제어

## 🛠️ 문제 해결

### FFmpeg 오류
- FFmpeg가 설치되어 있는지 확인
- PATH에 FFmpeg가 포함되어 있는지 확인

### OpenAI API 오류
- API 키가 올바른지 확인
- API 사용량 한도 확인

### Queue Worker 오류
- 데이터베이스 연결 확인
- Queue 테이블 존재 여부 확인
EOF

# 9. README 파일 생성
echo "📚 README 파일 생성 중..."
cat > "$PACKAGE_DIR/README.md" << 'EOF'
# AI 일괄 체점 시스템

Laravel 기반 AI 일괄 체점 시스템으로, OpenAI Whisper와 GPT-4를 사용하여 영상 파일을 자동으로 평가하는 시스템입니다.

## 🎯 주요 기능

- **자동 음성 인식**: OpenAI Whisper를 사용한 정확한 음성-텍스트 변환
- **AI 평가**: GPT-4를 사용한 영어 발표 평가 (발음, 어휘, 유창성)
- **대용량 파일 처리**: 25MB 이상 파일의 분할 처리 지원
- **실시간 모니터링**: 진행 상황 실시간 확인 및 제어
- **관리자 인터페이스**: 직관적인 웹 기반 관리 도구

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

## 📁 파일 구조

```
app/
├── Http/Controllers/
│   └── AiEvaluationController.php    # AI 평가 컨트롤러
├── Jobs/
│   └── BatchAiEvaluationJob.php      # AI 평가 작업
├── Models/
│   └── AiEvaluation.php             # AI 평가 모델
└── Services/
    └── OpenAiService.php            # OpenAI 서비스

resources/views/admin/
└── batch-evaluation.blade.php       # 일괄 평가 관리 페이지

database/migrations/
└── *_create_ai_evaluations_table.php # AI 평가 테이블 마이그레이션
```

## 🚀 빠른 시작

1. **의존성 설치**
   ```bash
   composer install
   ```

2. **환경 설정**
   ```bash
   cp .env.example .env
   # OpenAI API 키 설정
   ```

3. **데이터베이스 마이그레이션**
   ```bash
   php artisan migrate
   ```

4. **Queue Worker 시작**
   ```bash
   php artisan queue:work
   ```

5. **관리자 페이지 접속**
   - `/admin/batch-evaluation` 경로로 접속

## 📊 평가 기준

- **발음 점수 (0-10점)**: 정확한 발음과 자연스러운 억양 및 전달력
- **어휘 점수 (0-10점)**: 올바른 어휘 및 표현 사용
- **유창성 점수 (0-10점)**: 유창성 수준

## 🔧 설정

### OpenAI API 키 설정
```env
OPENAI_API_KEY=your_openai_api_key_here
```

### Queue 설정
```env
QUEUE_CONNECTION=database
```

### 파일 저장소 설정
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=ap-northeast-2
AWS_BUCKET=your_bucket_name
```

## 📈 성능 최적화

- **청크 분할**: 대용량 파일을 5분 단위로 분할 처리
- **압축 최적화**: MP3 압축으로 파일 크기 최소화
- **Queue 처리**: 비동기 처리로 서버 부하 분산
- **에러 처리**: 실패한 작업 자동 재시도

## 🛠️ 문제 해결

### 일반적인 문제들

1. **FFmpeg 설치 필요**
   ```bash
   # Ubuntu/Debian
   sudo apt install ffmpeg
   
   # CentOS/RHEL
   sudo yum install ffmpeg
   
   # macOS
   brew install ffmpeg
   ```

2. **OpenAI API 키 설정**
   ```bash
   php artisan config:clear
   ```

3. **Queue Worker 재시작**
   ```bash
   php artisan queue:restart
   ```

## 📝 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.

## 🤝 기여하기

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📞 지원

문제가 발생하거나 질문이 있으시면 이슈를 생성해 주세요.
EOF

# 10. Supervisor 설정 파일 생성
echo "⚙️ Supervisor 설정 파일 생성 중..."
cat > "$PACKAGE_DIR/supervisor-queue-worker.conf" << 'EOF'
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
stdout_logfile=/var/log/supervisor/laravel-queue-worker.log
stopwaitsecs=3600
EOF

# 11. 배포 스크립트 생성
echo "🚀 배포 스크립트 생성 중..."
cat > "$PACKAGE_DIR/deploy.sh" << 'EOF'
#!/bin/bash

# AI 일괄 체점 시스템 배포 스크립트

echo "🚀 AI 일괄 체점 시스템 배포 시작..."

# 1. 의존성 설치
echo "📦 Composer 의존성 설치 중..."
composer install --no-dev --optimize-autoloader

# 2. 환경 설정
echo "⚙️ 환경 설정 중..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚠️  .env 파일이 생성되었습니다. OpenAI API 키를 설정해주세요."
fi

# 3. 애플리케이션 키 생성
echo "🔑 애플리케이션 키 생성 중..."
php artisan key:generate

# 4. 캐시 클리어
echo "🧹 캐시 클리어 중..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 5. 데이터베이스 마이그레이션
echo "🗄️ 데이터베이스 마이그레이션 실행 중..."
php artisan migrate --force

# 6. 스토리지 링크
echo "🔗 스토리지 링크 생성 중..."
php artisan storage:link

# 7. 권한 설정
echo "🔐 권한 설정 중..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. Queue 테이블 생성
echo "📊 Queue 테이블 생성 중..."
php artisan queue:table
php artisan migrate

# 9. Supervisor 설정
echo "⚙️ Supervisor 설정 중..."
if [ -f supervisor-queue-worker.conf ]; then
    sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start laravel-queue-worker:*
fi

echo "✅ 배포 완료!"
echo "📝 다음 단계:"
echo "1. .env 파일에서 OpenAI API 키 설정"
echo "2. Queue Worker 시작: php artisan queue:work"
echo "3. 관리자 페이지 접속: /admin/batch-evaluation"
EOF

chmod +x "$PACKAGE_DIR/deploy.sh"

# 12. 압축 파일 생성
echo "📦 압축 파일 생성 중..."
ZIP_FILE="ai-evaluation-system-$(date +%Y%m%d_%H%M%S).zip"
zip -r "$ZIP_FILE" "$PACKAGE_DIR"

# 13. 정리
echo "🧹 임시 파일 정리 중..."
rm -rf "$PACKAGE_DIR"

echo "✅ AI 일괄 체점 시스템 패키지 생성 완료!"
echo "📦 생성된 파일: $ZIP_FILE"
echo "📁 압축 해제 후 INSTALLATION.md 파일을 참고하여 설치하세요."
echo ""
echo "🎯 주요 파일들:"
echo "  - app/Http/Controllers/AiEvaluationController.php"
echo "  - app/Jobs/BatchAiEvaluationJob.php"
echo "  - app/Services/OpenAiService.php"
echo "  - app/Models/AiEvaluation.php"
echo "  - resources/views/admin/batch-evaluation.blade.php"
echo "  - database/migrations/*_create_ai_evaluations_table.php"
echo ""
echo "🚀 사용법:"
echo "  1. 압축 해제: unzip $ZIP_FILE"
echo "  2. 의존성 설치: composer install"
echo "  3. 환경 설정: cp .env.example .env"
echo "  4. 마이그레이션: php artisan migrate"
echo "  5. Queue Worker: php artisan queue:work"
