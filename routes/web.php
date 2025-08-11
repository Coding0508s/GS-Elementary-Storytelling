<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoSubmissionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JudgeController;

// 메인 페이지 - 개인정보 동의 페이지로 리다이렉트
Route::get('/', function () {
    return redirect()->route('privacy.consent');
});

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

// 업로드 성공 페이지
Route::get('/upload-success', [VideoSubmissionController::class, 'showUploadSuccess'])
    ->name('upload.success');

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
        
        // 데이터 다운로드
        Route::get('/download/excel', [AdminController::class, 'downloadExcel'])
            ->name('download.excel');
        
        // 통계 페이지
        Route::get('/statistics', [AdminController::class, 'statistics'])
            ->name('statistics');
        
        // 데이터 초기화
        Route::get('/reset-confirmation', [AdminController::class, 'showResetConfirmation'])
            ->name('reset.confirmation');
        
        Route::post('/reset-execute', [AdminController::class, 'executeReset'])
            ->name('reset.execute');
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
        
        Route::get('/video/{id}/stream-url', [JudgeController::class, 'getVideoStreamUrl'])
            ->name('video.stream-url');
    });
});
