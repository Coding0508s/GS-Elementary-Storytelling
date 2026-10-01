<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VideoSubmission;
use App\Services\SolapiSmsService;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $submission;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\VideoSubmission  $submission
     * @return void
     */
    public function __construct(VideoSubmission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! config('services.solapi.api_key') || ! config('services.solapi.from_number')) {
            Log::info('Solapi service is not configured. Skipping SMS.');
            return;
        }

        try {
            $smsService = new SolapiSmsService();
            $result = $smsService->sendUploadCompletionNotification($this->submission);

            if ($result['success']) {
                Log::info('SMS notification job sent successfully.', [
                    'submission_id' => $this->submission->id,
                    'phone' => $this->submission->parent_phone,
                    'group_id' => $result['group_id'] ?? null,
                ]);
            } else {
                Log::error('SMS notification job failed.', [
                    'submission_id' => $this->submission->id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::critical('SMS notification job failed with exception.', [
                'submission_id' => $this->submission->id,
                'exception' => $e->getMessage()
            ]);
            // 실패 시 다시 시도하도록 예외를 다시 던질 수 있습니다.
            // $this->fail($e);
        }
    }
}
