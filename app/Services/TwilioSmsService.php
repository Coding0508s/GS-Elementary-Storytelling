<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class TwilioSmsService
{
    private $client;
    private $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = config('services.twilio.from_number');
    }

    /**
     * SMS 메시지 전송
     */
    public function sendSms($to, $message)
    {
        try {
            // 한국 전화번호 형식으로 변환 (010-1234-5678 -> +8210-1234-5678)
            $formattedNumber = $this->formatPhoneNumber($to);
            
            $message = $this->client->messages->create(
                $formattedNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            Log::info('SMS 전송 성공', [
                'to' => $formattedNumber,
                'message_sid' => $message->sid,
                'status' => $message->status
            ]);

            return [
                'success' => true,
                'message_sid' => $message->sid,
                'status' => $message->status
            ];

        } catch (Exception $e) {
            Log::error('SMS 전송 실패', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 비디오 업로드 완료 알림 메시지 전송
     */
    public function sendUploadCompletionNotification($submission)
    {
        $message = $this->buildUploadCompletionMessage($submission);
        
        return $this->sendSms($submission->parent_phone, $message);
    }

    /**
     * 업로드 완료 메시지 구성
     */
    private function buildUploadCompletionMessage($submission)
    {
        $studentName = $submission->student_name_korean;
        $institutionName = $submission->institution_name;
        $className = $submission->class_name;
        $unitTopic = $submission->unit_topic ?: '미지정';
        $receiptNumber = str_pad($submission->id, 5, '0', STR_PAD_LEFT);
        
        $message = "[GrapeSeed]\n";
        $message .= "{$studentName}학생의 영상 업로드 완료!\n";
        $message .= "접수번호: GSK-{$receiptNumber}\n";
        $message .= "참여해주셔서 감사합니다! 🎉";

        return $message; 
    }

    /**
     * 한국 전화번호를 Twilio 형식으로 변환
     */
    private function formatPhoneNumber($phone)
    {
        // 하이픈 제거
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // 010으로 시작하는 경우 +82로 변환
        if (preg_match('/^010/', $phone)) {
            $phone = '+82' . substr($phone, 1);
        }
        
        // 이미 +82로 시작하는 경우 그대로 사용
        if (preg_match('/^\+82/', $phone)) {
            return $phone;
        }
        
        // 다른 형식의 경우 +82 추가
        if (preg_match('/^82/', $phone)) {
            return '+' . $phone;
        }
        
        // 기본적으로 +82 추가
        return '+82' . $phone;
    }

    /**
     * 전화번호 유효성 검사
     */
    public function validatePhoneNumber($phone)
    {
        // 하이픈 제거
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // 한국 휴대폰 번호 패턴 (010, 011, 016, 017, 018, 019)
        $pattern = '/^(010|011|016|017|018|019)[0-9]{7,8}$/';
        
        return preg_match($pattern, $cleanPhone);
    }
}
