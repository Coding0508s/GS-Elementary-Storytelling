<?php

namespace App\Jobs;

use App\Services\AdminAlimtalkDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAdminAlimtalkBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public string $dispatchId, public array $recipientIds) {}

    public function handle(AdminAlimtalkDispatchService $service): void
    {
        $service->processBatch($this->dispatchId, $this->recipientIds);
    }

    public function failed(?\Throwable $exception): void
    {
        app(AdminAlimtalkDispatchService::class)->failBatch($this->dispatchId, $this->recipientIds);
    }
}
