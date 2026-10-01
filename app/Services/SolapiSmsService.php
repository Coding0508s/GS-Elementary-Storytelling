<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Nurigo\Solapi\Exceptions\MessageNotReceivedException;
use Nurigo\Solapi\Models\Message;
use Nurigo\Solapi\Services\SolapiMessageService;

class SolapiSmsService
{
    /**
     * 테스트에서 SDK 호출을 가로챌 때만 넣습니다.
     * 타입을 지정하지 않은 이유는, Laravel 컨테이너가 Solapi 클라이언트를
     * 자동으로 만들다가 API 키 인자 때문에 실패하는 것을 막기 위해서입니다.
     */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client;
    }

    /**
     * SMS 메시지 전송
     */
    public function sendSms($to, $message)
    {
        $formattedNumber = $this->formatPhoneNumber($to);

        try {
            $client = $this->client();
            $fromNumber = $this->fromNumber();

            // Solapi는 하이픈, +82 를 받지 않습니다. 숫자만 넣습니다.
            $solapiMessage = new Message();
            $solapiMessage->setTo($formattedNumber)
                ->setFrom($fromNumber)
                ->setText($message);

            $result = $client->send($solapiMessage);

            if (! empty($result->failedMessageList)) {
                $failed = $result->failedMessageList[0];
                $error = $failed->statusMessage ?? '메시지 접수에 실패했습니다.';

                Log::error('SMS 전송 실패', [
                    'to' => $formattedNumber,
                    'error' => $error,
                    'status_code' => $failed->statusCode ?? null,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            $groupId = $result->groupInfo->groupId ?? null;
            $status = $result->groupInfo->status ?? null;

            Log::info('SMS 전송 성공', [
                'to' => $formattedNumber,
                'group_id' => $groupId,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'message_id' => $groupId,
                'group_id' => $groupId,
                'status' => $status,
            ];
        } catch (MessageNotReceivedException $e) {
            $failed = $e->getFailedMessageList()[0] ?? null;
            $error = $failed->statusMessage ?? $e->getMessage();

            Log::error('SMS 전송 실패', [
                'to' => $formattedNumber,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
            ];
        } catch (Exception $e) {
            Log::error('SMS 전송 실패', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
        $receiptNumber = str_pad($submission->id, 5, '0', STR_PAD_LEFT);

        $message = "[GrapeSEED]\n";
        $message .= empty($submission->video_file_path)
            ? "{$studentName} 학생의 세미나 신청이 완료되었습니다.\n"
            : "{$studentName}학생의 영상 업로드 완료!\n";
        $message .= "접수번호: GSK-{$receiptNumber}\n";
        $message .= "참여해주셔서 감사합니다! 🎉";

        return $message;
    }

    /**
     * 한국 휴대폰 번호를 Solapi 형식(01012345678)으로 변환
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        // 821012345678 처럼 국가번호가 붙어 있으면 국내 형식으로 되돌립니다.
        if (str_starts_with($phone, '82') && strlen($phone) >= 11) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }

    /**
     * 전화번호 유효성 검사
     */
    public function validatePhoneNumber($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($cleanPhone, '82') && strlen($cleanPhone) >= 11) {
            $cleanPhone = '0'.substr($cleanPhone, 2);
        }

        $pattern = '/^(010|011|016|017|018|019)[0-9]{7,8}$/';

        return preg_match($pattern, $cleanPhone);
    }

    private function client(): SolapiMessageService
    {
        if ($this->client instanceof SolapiMessageService) {
            return $this->client;
        }

        $apiKey = config('services.solapi.api_key');
        $apiSecret = config('services.solapi.api_secret');

        if (! $apiKey || ! $apiSecret) {
            throw new Exception('Solapi API 키 설정이 없습니다.');
        }

        return new SolapiMessageService($apiKey, $apiSecret);
    }

    private function fromNumber(): string
    {
        $fromNumber = preg_replace('/[^0-9]/', '', (string) config('services.solapi.from_number'));

        if ($fromNumber === '') {
            throw new Exception('Solapi 발신번호 설정이 없습니다.');
        }

        return $fromNumber;
    }
}
