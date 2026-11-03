<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('alimtalk:close {dispatch} {--confirmed-stopped : Worker와 외부 요청의 종료 및 제공사 기록을 확인함}', function () {
    if (! $this->option('confirmed-stopped')) {
        $this->error('먼저 해당 worker와 외부 요청이 종료되었는지, 제공사 발송 기록을 확인한 뒤 --confirmed-stopped를 지정하세요. 자동 재발송하지 않습니다.');

        return 1;
    }
    app(\App\Services\AdminAlimtalkDispatchService::class)->closeStopped($this->argument('dispatch'));
    $this->info('작업을 종료했습니다. 접수 여부가 불명확한 번호는 확인 필요로 보존했습니다.');

    return 0;
})->purpose('중단을 확인한 알림톡 작업 종료 및 종류별 잠금 해제');

Artisan::command('alimtalk:sync-results {--limit=20 : 이번에 조회할 그룹 수(최대 20)}', function () {
    $result = app(\App\Services\AdminAlimtalkDispatchService::class)->syncDeliveryResults((int) $this->option('limit'));
    if (! $result['ready']) {
        $this->error('발송 결과 조회용 데이터베이스 업데이트가 필요합니다.');

        return 1;
    }
    $this->info("발송 결과 {$result['checked']}개 그룹 조회 · 조회 오류 {$result['errors']}건 (재발송 없음)");

    return $result['errors'] > 0 ? 1 : 0;
})->purpose('SOLAPI 최종 발송 결과 조회 및 접수 목록 반영');

Schedule::command('alimtalk:sync-results')->everyMinute()->withoutOverlapping(10);
