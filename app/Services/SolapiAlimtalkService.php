<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Nurigo\Solapi\Exceptions\MessageNotReceivedException;
use Nurigo\Solapi\Models\Kakao\KakaoOption;
use Nurigo\Solapi\Models\Message;
use Nurigo\Solapi\Services\SolapiMessageService;

class SolapiAlimtalkService
{
    /**
     * 테스트에서 SDK 호출을 가로챌 때만 넣습니다.
     * 타입을 지정하지 않은 이유는, Laravel이 API 키 없이 클라이언트를 만들다 실패하는 것을 막기 위해서입니다.
     */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client;
    }

    /**
     * 채널 ID와 템플릿 ID가 있어야 알림톡을 보낼 수 있습니다.
     */
    public function isReady(): bool
    {
        return $this->pfId() !== '' && $this->templateId() !== '';
    }

    /**
     * 접수 목록의 학부모 번호로 알림톡 발송을 요청합니다.
     * 카카오톡 실패를 문자로 대신 보내지 않습니다.
     *
     * @return array{requested: int, failed: int, skipped: int}
     */
    public function sendToApplications(Collection $applications): array
    {
        if (! $this->isReady()) {
            throw new Exception('템플릿이 연결되지 않았습니다.');
        }

        $messages = [];
        $skipped = 0;

        foreach ($applications as $application) {
            $phone = $this->formatPhoneNumber($application->parent_phone);
            if (! $this->isMobilePhone($phone)) {
                $skipped++;
                continue;
            }

            $messages[] = $this->makeMessage($application, $phone);
        }

        $requested = 0;
        $failed = 0;

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $result = $this->client()->send($chunk);
                $failedInChunk = count($result->failedMessageList ?? []);
                $failed += $failedInChunk;
                $requested += count($chunk) - $failedInChunk;
            } catch (MessageNotReceivedException $e) {
                $failedInChunk = count($e->getFailedMessageList());
                $failed += $failedInChunk > 0 ? $failedInChunk : count($chunk);
                $requested += max(0, count($chunk) - $failedInChunk);
                Log::error('알림톡 발송 실패', [
                    'error' => $e->getMessage(),
                    'failed' => $failedInChunk,
                ]);
            } catch (\Throwable $e) {
                $failed += count($chunk);
                Log::error('알림톡 발송 오류', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'requested' => $requested,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    private function makeMessage($application, string $phone): Message
    {
        $kakaoOption = new KakaoOption();
        $kakaoOption->setPfId($this->pfId())
            ->setTemplateId($this->templateId())
            ->setDisableSms(true)
            ->setVariables((object) [
                (string) config('services.solapi.kakao_var_student_name') => (string) $application->student_name_korean,
                (string) config('services.solapi.kakao_var_receipt_number') => (string) $application->receipt_number,
            ]);

        $message = new Message();
        $message->setTo($phone)
            ->setKakaoOptions($kakaoOption);

        return $message;
    }

    private function client(): SolapiMessageService
    {
        if ($this->client instanceof SolapiMessageService) {
            return $this->client;
        }

        $apiKey = config('services.solapi.api_key');
        $apiSecret = config('services.solapi.api_secret');

        if (! $apiKey || ! $apiSecret) {
            throw new Exception('Solapi API 키 설정이 없습니다. 서버 .env의 SOLAPI_API_KEY와 SOLAPI_API_SECRET을 확인해주세요.');
        }

        if (! class_exists(SolapiMessageService::class)) {
            throw new Exception('Solapi SDK가 서버에 설치되어 있지 않습니다. 서버에서 composer install을 실행해주세요.');
        }

        return new SolapiMessageService($apiKey, $apiSecret);
    }

    private function pfId(): string
    {
        return trim((string) config('services.solapi.kakao_pf_id'));
    }

    private function templateId(): string
    {
        return trim((string) config('services.solapi.kakao_template_id'));
    }

    private function formatPhoneNumber($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($phone, '82') && strlen($phone) >= 11) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }

    private function isMobilePhone(string $phone): bool
    {
        return (bool) preg_match('/^(010|011|016|017|018|019)[0-9]{7,8}$/', $phone);
    }
}
