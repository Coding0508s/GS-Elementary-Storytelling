<?php

namespace App\Jobs;

use App\Services\AdminAlimtalkDispatchService;

/** A worker running older code must not cancel an external reservation and lose the edit. */
class CancelAlimtalkForChange extends ManageAdminAlimtalkSchedule
{
    public function __construct(string $dispatchId, string $groupId)
    {
        parent::__construct($dispatchId, $groupId, true);
    }

    public function handle(AdminAlimtalkDispatchService $service): void
    {
        if (! method_exists($service, 'updateDispatch')) {
            throw new \LogicException('수정·삭제 기능을 사용하려면 worker를 재시작해주세요.');
        }
        parent::handle($service);
    }
}
