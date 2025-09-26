<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VideoSubmission;
use Illuminate\Support\Facades\Log;

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
                SendSmsNotificationJob::dispatch($submission->id);
            }
        } catch (\Exception $e) {
            Log::error('ProcessVideoSubmission Job 실패', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
        }
    }
}
