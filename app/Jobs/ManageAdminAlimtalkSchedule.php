<?php

namespace App\Jobs;

use App\Services\AdminAlimtalkDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ManageAdminAlimtalkSchedule implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public bool $failOnTimeout = true;

    public function __construct(public string $dispatchId, public string $groupId, public bool $cancel) {}

    public function handle(AdminAlimtalkDispatchService $service): void
    {
        $service->manageScheduleGroup($this->dispatchId, $this->groupId, $this->cancel);
    }

    public function failed(?\Throwable $exception): void
    {
        app(AdminAlimtalkDispatchService::class)->scheduleManagementFailed($this->dispatchId, $this->groupId, $this->cancel);
    }
}
