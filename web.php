<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoSubmissionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JudgeController;

// 메인 페이지 - 이벤트 소개 페이지
Route::get('/', [VideoSubmissionController::class, 'showEventIntro'])
    ->name('event.intro');

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

// 기관명 자동완성 API
Route::get('/api/institutions', [VideoSubmissionController::class, 'getInstitutions'])
    ->name('api.institutions');

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
        
        // 심사위원 계정 자동 생성
        Route::post('/assignments/create-judges', [AdminController::class, 'createMissingJudges'])
            ->name('assignment.create.judges');
        
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
        
        // 2차 예선 진출 관리
        Route::post('/qualify-second-round', [AdminController::class, 'qualifySecondRound'])
            ->name('qualify.second.round');
        
        Route::get('/second-round-qualifiers', [AdminController::class, 'secondRoundQualifiers'])
            ->name('second.round.qualifiers');
        
        Route::get('/download/second-round-qualifiers', [AdminController::class, 'downloadSecondRoundQualifiers'])
            ->name('download.second.round.qualifiers');
        
        Route::post('/reset-qualification', [AdminController::class, 'resetQualificationStatus'])
            ->name('reset.qualification');
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
    });
});
