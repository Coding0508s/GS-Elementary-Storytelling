<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoSubmissionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\S3UploadController;

// 메인 페이지 - 바로 개인정보 동의 페이지로 이동
Route::get('/', [VideoSubmissionController::class, 'showPrivacyConsent'])
    ->name('event.intro');

// 기존 이벤트 소개 페이지 (주석처리)
// Route::get('/event-intro', [VideoSubmissionController::class, 'showEventIntro'])
//     ->name('event.intro.original');

// 개인정보 수집 동의 관련 라우트
Route::get('/privacy-consent', [VideoSubmissionController::class, 'showPrivacyConsent'])
    ->name('privacy.consent');

Route::post('/privacy-consent', [VideoSubmissionController::class, 'processPrivacyConsent'])
    ->name('privacy.consent.process');

// 비디오 업로드 관련 라우트
Route::get('/upload', [VideoSubmissionController::class, 'showUploadForm'])
    ->name('upload.form');

Route::post('/upload', [VideoSubmissionController::class, 'uploadVideo'])
    ->name('upload.process');

// 휴대폰 OTP 인증 (발송/검증)
Route::post('/api/otp/send', [VideoSubmissionController::class, 'sendOtp'])
    ->name('api.otp.send');

Route::post('/api/otp/verify', [VideoSubmissionController::class, 'verifyOtp'])
    ->name('api.otp.verify');

// 업로드 성공 페이지
Route::get('/upload-success', [VideoSubmissionController::class, 'showUploadSuccess'])
    ->name('upload.success');

// 기관명 자동완성 API
Route::get('/api/institutions', [VideoSubmissionController::class, 'getInstitutions'])
    ->name('api.institutions');

// 세션 초기화 전용 라우트
Route::get('/reset-session', [VideoSubmissionController::class, 'resetSession'])
    ->name('session.reset');

// CSRF 토큰 갱신 API
Route::get('/api/csrf-token', [VideoSubmissionController::class, 'refreshCsrfToken'])
    ->name('api.csrf-token');

// S3 직접 업로드 관련 API
Route::post('/api/s3/presigned-url', [S3UploadController::class, 'generatePresignedUrl'])
    ->name('api.s3.presigned-url');

Route::post('/api/s3/upload-complete', [S3UploadController::class, 'uploadComplete'])
    ->name('api.s3.upload-complete');

Route::delete('/api/s3/delete-file', [S3UploadController::class, 'deleteFile'])
    ->name('api.s3.delete-file');

Route::get('/api/s3/file-url', [S3UploadController::class, 'getFileUrl'])
    ->name('api.s3.file-url');

// ============================================
// 관리자 페이지 라우트
// ============================================

// 관리자 로그인 (인증 불필요)
Route::get('/admin/login', [AdminController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminController::class, 'login'])
    ->name('admin.login.process');

// 관리자 인증이 필요한 라우트들
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 대시보드
        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');
        
        // 로그아웃
        Route::post('/logout', [AdminController::class, 'logout'])
            ->name('logout');
        
        // 심사 관련
        Route::get('/evaluations', [AdminController::class, 'evaluationList'])
            ->name('evaluation.list');
        
        Route::get('/evaluations/{id}', [AdminController::class, 'showEvaluation'])
            ->name('evaluation.show');
        
        Route::post('/evaluations/{id}', [AdminController::class, 'storeEvaluation'])
            ->name('evaluation.store');
        
        // 영상 배정 관련
        Route::get('/assignments', [AdminController::class, 'assignmentList'])
            ->name('assignment.list');
        
        Route::post('/assignments', [AdminController::class, 'assignVideo'])
            ->name('assignment.assign');
        
        Route::delete('/assignments/{id}', [AdminController::class, 'cancelAssignment'])
            ->name('assignment.cancel');
        
        Route::post('/assignments/auto', [AdminController::class, 'autoAssign'])
            ->name('assignment.auto');
        
        Route::post('/assignments/reassign-all', [AdminController::class, 'reassignAll'])
            ->name('assignment.reassign.all');
        
        // 데이터 다운로드
        Route::get('/download/excel', [AdminController::class, 'downloadExcel'])
            ->name('download.excel');
        
        // 통계 페이지
        Route::get('/statistics', [AdminController::class, 'statistics'])
            ->name('statistics');
        
        // 기관명 관리
        Route::get('/institutions', [AdminController::class, 'institutionList'])
            ->name('institution.list');
        
        Route::post('/institutions', [AdminController::class, 'addInstitution'])
            ->name('institution.add');
        
        Route::put('/institutions/{id}', [AdminController::class, 'updateInstitution'])
            ->name('institution.update');
        
        Route::delete('/institutions/{id}', [AdminController::class, 'deleteInstitution'])
            ->name('institution.delete');
        
        Route::post('/institutions/{id}/toggle', [AdminController::class, 'toggleInstitution'])
            ->name('institution.toggle');
        
        // 데이터 초기화
        Route::get('/reset-confirmation', [AdminController::class, 'showResetConfirmation'])
            ->name('reset.confirmation');
        
        Route::post('/reset-execute', [AdminController::class, 'executeReset'])
            ->name('reset.execute');
        
        // 비밀번호 재설정
        Route::get('/password-reset', [AdminController::class, 'showPasswordReset'])
            ->name('password.reset');
        
        // 일괄 AI 채점 관련
        Route::post('/batch-ai-evaluation/start', [AdminController::class, 'startBatchAiEvaluation'])
            ->name('batch.ai.evaluation.start');
        
        Route::get('/batch-ai-evaluation/progress', [AdminController::class, 'getBatchAiEvaluationProgress'])
            ->name('batch.ai.evaluation.progress');
        
        Route::post('/batch-ai-evaluation/retry', [AdminController::class, 'retryFailedAiEvaluations'])
            ->name('batch.ai.evaluation.retry');
        
        // 영상 일괄 채점 페이지
        Route::get('/batch-evaluation', [AdminController::class, 'batchEvaluationList'])
            ->name('batch.evaluation.list');
        
        // 개별 영상 AI 채점
        Route::post('/batch-evaluation/start-single/{submissionId}', [AdminController::class, 'startSingleAiEvaluation'])
            ->name('batch.evaluation.start.single');
        
        
        Route::post('/password-reset', [AdminController::class, 'resetPassword'])
            ->name('password.reset.execute');
        
        // 심사위원 관리
        Route::get('/judge-management', [AdminController::class, 'showJudgeManagement'])
            ->name('judge.management');
        
        Route::post('/judge-create', [AdminController::class, 'createJudge'])
            ->name('judge.create');
        
        Route::delete('/judge-delete/{id}', [AdminController::class, 'deleteJudge'])
            ->name('judge.delete');
        
        Route::patch('/judge-toggle-status/{id}', [AdminController::class, 'toggleJudgeStatus'])
            ->name('judge.toggle.status');

        // AI 채점 결과 관리 (구체적인 라우트를 먼저 정의)
        Route::get('/ai-evaluations/export', [AdminController::class, 'downloadAiEvaluationExcel'])
            ->name('ai-evaluations.export');
        
        // AI 평가 관리
        Route::get('/ai-evaluations', [AdminController::class, 'aiEvaluationList'])
            ->name('ai.evaluation.list');
        
        Route::get('/ai-evaluations/{id}', [AdminController::class, 'showAiEvaluation'])
            ->name('ai.evaluation.show');
        
        Route::delete('/ai-evaluations/reset', [AdminController::class, 'resetAiEvaluations'])
            ->name('ai-evaluations.reset');
        
        Route::get('/ai-evaluation/{id}', [AdminController::class, 'getAiEvaluationDetail'])
            ->name('ai-evaluation.detail');
        
        // 영상 보기
        Route::get('/video/{id}/view', [AdminController::class, 'viewVideo'])
            ->name('video.view');
        
        // AI 설정 관리
        Route::get('/ai-settings', [AdminController::class, 'aiSettings'])
            ->name('ai.settings');
        
        Route::post('/ai-settings', [AdminController::class, 'updateAiSettings'])
            ->name('ai.settings.update');
        
        // 2차 예선 진출 관리 (필요 없어서 주석처리)
        /*
        Route::post('/qualify-second-round', [AdminController::class, 'qualifySecondRound'])
            ->name('qualify.second.round');
        
        Route::get('/second-round-qualifiers', [AdminController::class, 'secondRoundQualifiers'])
            ->name('second.round.qualifiers');
        
        Route::get('/download/second-round-qualifiers', [AdminController::class, 'downloadSecondRoundQualifiers'])
            ->name('download.second.round.qualifiers');
        
        Route::post('/reset-qualification', [AdminController::class, 'resetQualificationStatus'])
            ->name('reset.qualification');
        */
        
        // 임시 라우트: 2차 예선 관련 페이지 접근 시 통계 페이지로 리다이렉트
        Route::get('/second-round-qualifiers', function() {
            return redirect()->route('admin.statistics');
        })->name('second.round.qualifiers');
        
    });
});

// ============================================
// 심사위원 페이지 라우트
// ============================================

// 심사위원 인증이 필요한 라우트들
Route::prefix('judge')->name('judge.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 대시보드
        Route::get('/dashboard', [JudgeController::class, 'dashboard'])
            ->name('dashboard');
        
        // 로그아웃
        Route::post('/logout', [JudgeController::class, 'logout'])
            ->name('logout');
        
        // 영상 목록
        Route::get('/videos', [JudgeController::class, 'videoList'])
            ->name('video.list');
        
        // 영상 심사
        Route::get('/evaluation/{id}', [JudgeController::class, 'showEvaluation'])
            ->name('evaluation.show');
        
        Route::post('/evaluation/{id}', [JudgeController::class, 'storeEvaluation'])
            ->name('evaluation.store');
        
        // 심사 시작
        Route::post('/evaluation/{id}/start', [JudgeController::class, 'startEvaluation'])
            ->name('evaluation.start');
        
        // 심사 결과 수정
        Route::get('/evaluation/{id}/edit', [JudgeController::class, 'editEvaluation'])
            ->name('evaluation.edit');
        
        Route::put('/evaluation/{id}/edit', [JudgeController::class, 'updateEvaluation'])
            ->name('evaluation.update');
        
        // 영상 다운로드 및 스트리밍
        Route::get('/video/{id}/download', [JudgeController::class, 'downloadVideo'])
            ->name('video.download');
        
        Route::get('/video/{id}/stream', [JudgeController::class, 'getVideoStreamUrl'])
            ->name('video.stream');

        // AI 평가
        Route::post('/ai-evaluate/{id}', [JudgeController::class, 'performAiEvaluation'])
            ->name('ai.evaluation.perform');
        
        Route::get('/ai-evaluation/{id}/result', [JudgeController::class, 'showAiEvaluation'])
            ->name('ai.evaluation.result');
        
        Route::get('/ai-result/{id}', [JudgeController::class, 'getAiResult'])
            ->name('ai.result.show');
    });
});

// ============================================
// 모니터링 시스템 라우트
// ============================================

// 모니터링 라우트 (관리자 인증 필요)
Route::prefix('admin/monitoring')->name('admin.monitoring.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 모니터링 대시보드
        Route::get('/dashboard', [App\Http\Controllers\MonitoringController::class, 'dashboard'])
            ->name('dashboard');
        
        // API 엔드포인트들
        Route::get('/server-status', [App\Http\Controllers\MonitoringController::class, 'getServerStatus'])
            ->name('server-status');
        
        Route::get('/concurrent-users', [App\Http\Controllers\MonitoringController::class, 'getConcurrentUsers'])
            ->name('concurrent-users');
        
        Route::get('/error-metrics', [App\Http\Controllers\MonitoringController::class, 'getErrorMetrics'])
            ->name('error-metrics');
        
        Route::get('/alerts', [App\Http\Controllers\MonitoringController::class, 'getAlerts'])
            ->name('alerts');
        
        // 엑셀 리포트 다운로드
        Route::post('/export-excel', [App\Http\Controllers\MonitoringController::class, 'exportExcel'])
            ->name('export-excel');
    });
});
