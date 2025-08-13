<?php

namespace App\Services;

use App\Models\VideoSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * SMS 발송
     */
    public function sendSMS($phone, $message)
    {
        try {
            // 실제 운영 환경에서는 SMS API를 사용
            // 예: NHN Cloud SMS, 카카오 알림톡, 네이버 클라우드 등
            
            // 여기서는 로그로 대체
            Log::info('SMS 발송', [
                'phone' => $this->maskPhoneNumber($phone),
                'message' => $message,
                'sent_at' => now()
            ]);
            
            // 실제 SMS API 호출 예시 (주석 처리)
            /*
            $apiKey = env('SMS_API_KEY');
            $apiSecret = env('SMS_API_SECRET');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.sms-service.com/send', [
                'to' => $phone,
                'message' => $message,
                'from' => '1588-0000' // 발신번호
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('SMS 발송 실패: ' . $response->body());
            }
            */
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('SMS 발송 오류', [
                'phone' => $this->maskPhoneNumber($phone),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * 이메일 발송 (선택사항)
     */
    public function sendEmail($email, $subject, $message)
    {
        try {
            // Laravel Mail을 사용한 이메일 발송
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                     ->subject($subject)
                     ->from(env('MAIL_FROM_ADDRESS', 'noreply@gs-education.com'));
            });
            
            Log::info('이메일 발송', [
                'email' => $this->maskEmail($email),
                'subject' => $subject,
                'sent_at' => now()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('이메일 발송 오류', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * 업로드 완료 알림 발송
     */
    public function sendUploadCompletionNotification(VideoSubmission $submission)
    {
        $message = $this->generateUploadCompletionMessage($submission);
        
        // SMS 발송
        $smsResult = $this->sendSMS($submission->parent_phone, $message);
        
        // 알림 발송 상태 업데이트
        $submission->update([
            'notification_sent' => $smsResult,
            'notification_sent_at' => $smsResult ? now() : null,
            'status' => $smsResult ? VideoSubmission::STATUS_COMPLETED : VideoSubmission::STATUS_FAILED
        ]);
        
        return $smsResult;
    }
    
    /**
     * 업로드 완료 메시지 생성
     */
    private function generateUploadCompletionMessage(VideoSubmission $submission)
    {
        $receiptNumber = str_pad($submission->id, 6, '0', STR_PAD_LEFT);
        
        return sprintf(
            "[GS Speech Contest] 안녕하세요. %s 학생의 영어 발표 동영상 업로드가 완료되었습니다. 접수번호: #%s 참여해주셔서 감사합니다.",
            $submission->student_name_korean,
            $receiptNumber
        );
    }
    
    /**
     * 전화번호 마스킹
     */
    private function maskPhoneNumber($phone)
    {
        if (strlen($phone) >= 11) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return '***-***-****';
    }
    
    /**
     * 이메일 주소 마스킹
     */
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $username = $parts[0];
            $domain = $parts[1];
            
            if (strlen($username) > 3) {
                $maskedUsername = substr($username, 0, 3) . str_repeat('*', strlen($username) - 3);
            } else {
                $maskedUsername = str_repeat('*', strlen($username));
            }
            
            return $maskedUsername . '@' . $domain;
        }
        
        return '***@***.***';
    }
    
    /**
     * 대량 알림 발송 (관리자용)
     */
    public function sendBulkNotifications($submissions, $message)
    {
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($submissions as $submission) {
            $result = $this->sendSMS($submission->parent_phone, $message);
            
            if ($result) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }
        
        Log::info('대량 알림 발송 완료', [
            'total' => count($submissions),
            'success' => $successCount,
            'failure' => $failureCount
        ]);
        
        return [
            'total' => count($submissions),
            'success' => $successCount,
            'failure' => $failureCount
        ];
    }
    
    /**
     * 대회 결과 발표 알림
     */
    public function sendContestResultNotification(VideoSubmission $submission, $result = null)
    {
        $message = sprintf(
            "[GS Speech Contest] %s 학생의 대회 참가 결과를 안내드립니다. %s 자세한 내용은 교육기관을 통해 확인해주세요. 참여해주셔서 감사합니다.",
            $submission->student_name_korean,
            $result ? "축하드립니다! {$result}에 선정되셨습니다. " : ""
        );
        
        return $this->sendSMS($submission->parent_phone, $message);
    }
} 