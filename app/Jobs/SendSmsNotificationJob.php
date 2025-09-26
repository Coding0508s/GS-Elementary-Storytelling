<?php

namespace App\Jobs;

use App\Models\VideoSubmission;
use App\Services\TwilioSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The ID of the created submission.
     */
    public int $submissionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $submissionId)
    {
        $this->submissionId = $submissionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = VideoSubmission::find($this->submissionId);
        if (!$submission) {
            return;
        }

        try {
            $twilioService = new TwilioSmsService();
            $twilioService->sendUploadCompletionNotification($submission);
        } catch (\Throwable $e) {
            Log::warning('SMS 알림 Job 실패', [
                'submission_id' => $this->submissionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}


