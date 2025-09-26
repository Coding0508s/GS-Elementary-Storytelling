<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VideoSubmission;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessVideoSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // 데이터베이스에 정보 저장
            $submission = VideoSubmission::create($this->data);

            // SMS 알림 발송
            if (config('services.twilio.account_sid')) {
                $this->sendSms($submission);
            }
        } catch (Throwable $e) {
            Log::error('ProcessVideoSubmission Job 실패', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
        }
    }

    /**
     * Send SMS Notification.
     *
     * @param VideoSubmission $submission
     */
    private function sendSms(VideoSubmission $submission)
    {
        try {
            $twilioService = new TwilioSmsService();
            $twilioService->sendUploadCompletionNotification($submission);
        } catch (Throwable $e) {
            Log::warning('SMS 알림 발송 실패 (Job 내에서)', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
