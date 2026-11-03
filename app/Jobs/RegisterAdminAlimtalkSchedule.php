<?php

namespace App\Jobs;

use App\Services\AdminAlimtalkDispatchService;

/** Separate class prevents an already-running pre-scheduling worker from sending immediately. */
class RegisterAdminAlimtalkSchedule extends SendAdminAlimtalkBatch
{
    public function handle(AdminAlimtalkDispatchService $service): void
    {
        if (! method_exists($service, 'requestScheduleManagement')) {
            throw new \LogicException('예약 기능이 추가되었습니다. worker를 재시작해주세요.');
        }
        parent::handle($service);
    }
}
