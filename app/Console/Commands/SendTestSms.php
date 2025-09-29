<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TwilioSmsService;
use App\Services\ReliableSmsService;
use App\Models\VideoSubmission;

class SendTestSms extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:test 
                            {phone : 전화번호 (예: 010-1234-5678)}
                            {--message= : 사용자 정의 메시지}
                            {--submission= : 특정 submission ID로 업로드 완료 메시지 발송}';

    /**
     * The console command description.
     */
    protected $description = 'SMS 발송 테스트 명령어';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $customMessage = $this->option('message');
        $submissionId = $this->option('submission');

        $this->info("SMS 발송 테스트 시작...");
        $this->info("전화번호: {$phone}");

        try {
            $smsService = new TwilioSmsService();
            $reliableService = new ReliableSmsService();

            if ($submissionId) {
                // 특정 submission의 업로드 완료 메시지 발송
                $submission = VideoSubmission::find($submissionId);
                if (!$submission) {
                    $this->error("Submission ID {$submissionId}를 찾을 수 없습니다.");
                    return 1;
                }

                $this->info("Submission ID {$submissionId}의 업로드 완료 메시지 발송 중...");
                $result = $smsService->sendUploadCompletionNotification($submission);

            } elseif ($customMessage) {
                // 사용자 정의 메시지 발송
                $this->info("사용자 정의 메시지 발송 중: {$customMessage}");
                $result = $smsService->sendSms($phone, $customMessage);

            } else {
                // 기본 테스트 메시지 발송
                $testMessage = "[GrapeSEED 테스트]\n안녕하세요! SMS 발송 테스트입니다.\n시간: " . date('Y-m-d H:i:s');
                $this->info("기본 테스트 메시지 발송 중...");
                $result = $smsService->sendSms($phone, $testMessage);
            }

            if ($result['success']) {
                $this->info("✅ SMS 발송 성공!");
                $this->info("Message SID: {$result['message_sid']}");
                $this->info("Status: {$result['status']}");
            } else {
                $this->error("❌ SMS 발송 실패!");
                $this->error("Error: {$result['error']}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ SMS 발송 중 예외 발생!");
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
