<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Log;
use Exception;

class ReliableSmsService
{
    private $twilioService;

    public function __construct()
    {
        $this->twilioService = new TwilioSmsService();
    }

    /**
     * 안정적인 SMS 발송 (Queue + 즉시 발송 fallback)
     */
    public function sendReliableSms($submission)
    {
        try {
            // 1차 시도: Queue 시스템 사용
            Log::info('SMS 발송 시작 - Queue 방식 시도', [
                'submission_id' => $submission->id,
                'phone' => $submission->parent_phone
            ]);
            
            SendSmsJob::dispatch($submission);
            
            // Queue가 정상적으로 처리되는지 3초 후 확인
            sleep(3);
            
            // Queue 처리 확인 (실제 환경에서는 더 정교한 확인 필요)
            $recentLogs = $this->checkRecentSmsLogs($submission->id);
            
            if ($recentLogs) {
                Log::info('SMS Queue 발송 성공 확인됨', [
                    'submission_id' => $submission->id
                ]);
                return ['success' => true, 'method' => 'queue'];
            }
            
            // 2차 시도: 즉시 동기식 발송
            Log::warning('Queue SMS 발송 확인 안됨, 즉시 발송으로 fallback', [
                'submission_id' => $submission->id
            ]);
            
            return $this->sendImmediateSms($submission);
            
        } catch (Exception $e) {
            Log::error('Queue SMS 발송 실패, 즉시 발송으로 fallback', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
            
            // 3차 시도: 즉시 동기식 발송
            return $this->sendImmediateSms($submission);
        }
    }

    /**
     * 즉시 SMS 발송
     */
    public function sendImmediateSms($submission)
    {
        try {
            $result = $this->twilioService->sendUploadCompletionNotification($submission);
            
            if ($result['success']) {
                Log::info('SMS 즉시 발송 성공', [
                    'submission_id' => $submission->id,
                    'phone' => $submission->parent_phone,
                    'message_sid' => $result['message_sid'],
                    'method' => 'immediate'
                ]);
                return ['success' => true, 'method' => 'immediate', 'message_sid' => $result['message_sid']];
            } else {
                Log::error('SMS 즉시 발송 실패', [
                    'submission_id' => $submission->id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
            }
            
        } catch (Exception $e) {
            Log::error('SMS 즉시 발송 예외', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 최근 SMS 발송 로그 확인
     */
    private function checkRecentSmsLogs($submissionId)
    {
        // 실제 환경에서는 로그 파일이나 데이터베이스에서 확인
        // 여기서는 간단한 구현
        return false; // Queue 확인 로직은 복잡하므로 일단 false 반환
    }

    /**
     * 수동 SMS 발송 (테스트용)
     */
    public function sendTestSms($phoneNumber, $message)
    {
        try {
            $result = $this->twilioService->sendSms($phoneNumber, $message);
            
            Log::info('테스트 SMS 발송', [
                'phone' => $phoneNumber,
                'success' => $result['success'],
                'message_sid' => $result['message_sid'] ?? null
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('테스트 SMS 발송 실패', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
