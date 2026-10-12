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
    public const KIND_COMPLETED = 'completed';

    public const KIND_MORNING = 'morning';

    public const KIND_ENTRY = 'entry';

    public const KIND_OTP = 'otp';

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
     * 채널 ID와 해당 알림의 템플릿 ID가 있어야 보낼 수 있습니다.
     */
    public function isReady(string $kind = self::KIND_COMPLETED): bool
    {
        return $this->pfId() !== '' && $this->templateId($kind) !== '';
    }

    /**
     * 유튜브 주소에서 https:// 를 뺍니다.
     * 템플릿 버튼은 https://#{입장링크} 로 등록하므로, 변수 값에 프로토콜이 있으면 주소가 두 번 붙습니다.
     */
    public function entryLinkValue(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new Exception('유튜브 링크를 입력해주세요.');
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! $this->isYoutubeHost($host)) {
            throw new Exception('유튜브 링크만 보낼 수 있습니다. https:// 로 시작하는 유튜브 주소를 입력해주세요.');
        }

        $value = $parts['host'];
        if (! empty($parts['port'])) {
            $value .= ':'.$parts['port'];
        }
        $value .= $parts['path'] ?? '';
        if (! empty($parts['query'])) {
            $value .= '?'.$parts['query'];
        }
        if (! empty($parts['fragment'])) {
            $value .= '#'.$parts['fragment'];
        }

        return $value;
    }

    /**
     * 버튼에 넣을 수 있는 주소는 유튜브 본편, 라이브, 짧은 주소입니다.
     */
    private function isYoutubeHost(string $host): bool
    {
        return $host === 'youtu.be'
            || $host === 'youtube.com'
            || str_ends_with($host, '.youtube.com')
            || str_ends_with($host, '.youtu.be');
    }

    /**
     * 접수 목록의 학부모 번호로 알림톡 발송을 요청합니다.
     * 카카오톡이 없으면 템플릿에 등록한 대체 문자로 보냅니다.
     *
     * @return array{requested: int, failed: int, skipped: int}
     */
    public function sendToApplications(Collection $applications, string $kind = self::KIND_COMPLETED, ?string $entryLink = null): array
    {
        if (! $this->isReady($kind)) {
            throw new Exception('템플릿이 연결되지 않았습니다.');
        }

        if ($this->fromNumber() === '') {
            throw new Exception('대체 문자 발신번호가 없습니다. SOLAPI_FROM_NUMBER를 확인해주세요.');
        }

        if ($kind === self::KIND_ENTRY) {
            $entryLink = $this->entryLinkValue((string) $entryLink);
        }

        $messages = [];
        $results = [];
        $skipped = 0;

        foreach ($applications as $application) {
            $phone = $this->formatPhoneNumber($application->parent_phone);
            $row = [
                'id' => $application->id,
                'receipt' => (string) $application->receipt_number,
                'name' => (string) $application->student_name_korean,
                'parent' => (string) $application->parent_name,
                'phone' => $phone,
                'status' => 'sent',
                'reason' => '',
            ];

            if (! $this->isMobilePhone($phone)) {
                $skipped++;
                $row['status'] = 'skipped';
                $row['reason'] = '휴대폰 번호가 아닙니다.';
                $results[] = $row;
                continue;
            }

            $messages[] = $this->makeMessage($application, $phone, $kind, $entryLink, null);
            $results[] = $row;
        }

        $delivery = $this->deliver($messages, $skipped);
        foreach ($results as $index => $row) {
            if ($row['status'] !== 'sent' || ! isset($delivery['failures'][$row['phone']])) {
                continue;
            }

            $results[$index]['status'] = 'failed';
            $results[$index]['reason'] = $delivery['failures'][$row['phone']];
        }

        $delivery['results'] = $results;

        return $delivery;
    }

    /**
     * 휴대폰 인증번호를 알림톡으로 보냅니다.
     * 카카오톡이 없으면 템플릿에 등록한 대체 문자로 보냅니다.
     *
     * @return array{requested: int, failed: int, skipped: int}
     */
    public function sendVerificationCode(string $phone, string $code): array
    {
        if (! $this->isReady(self::KIND_OTP)) {
            throw new Exception('템플릿이 연결되지 않았습니다.');
        }

        if ($this->fromNumber() === '') {
            throw new Exception('대체 문자 발신번호가 없습니다. SOLAPI_FROM_NUMBER를 확인해주세요.');
        }

        $phone = $this->formatPhoneNumber($phone);
        if (! $this->isMobilePhone($phone)) {
            return [
                'requested' => 0,
                'failed' => 0,
                'skipped' => 1,
            ];
        }

        $message = $this->makeMessage(null, $phone, self::KIND_OTP, null, $code);

        return $this->deliver([$message], 0);
    }

    /**
     * @param  array<int, Message>  $messages
     * @return array{requested: int, failed: int, skipped: int, failures: array<string, string>}
     */
    private function deliver(array $messages, int $skipped): array
    {
        $requested = 0;
        $failed = 0;
        $failures = [];

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $result = $this->client()->send($chunk);
                $failedInChunk = $this->recordFailures($result->failedMessageList ?? [], $failures);
                $failed += $failedInChunk;
                $requested += count($chunk) - $failedInChunk;
            } catch (MessageNotReceivedException $e) {
                $failedInChunk = $this->recordFailures($e->getFailedMessageList(), $failures);
                if ($failedInChunk === 0) {
                    $failedInChunk = $this->recordChunkFailure($chunk, $failures, $e->getMessage());
                }
                $failed += $failedInChunk;
                $requested += max(0, count($chunk) - $failedInChunk);
                Log::error('알림톡 발송 실패', [
                    'error' => $e->getMessage(),
                    'failed' => $failedInChunk,
                ]);
            } catch (\Throwable $e) {
                $failed += $this->recordChunkFailure($chunk, $failures, $e->getMessage());
                Log::error('알림톡 발송 오류', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'requested' => $requested,
            'failed' => $failed,
            'skipped' => $skipped,
            'failures' => $failures,
        ];
    }

    /**
     * @param  array<int, mixed>  $failedMessages
     * @param  array<string, string>  $failures
     */
    private function recordFailures(array $failedMessages, array &$failures): int
    {
        foreach ($failedMessages as $failedMessage) {
            $phone = $this->formatPhoneNumber($failedMessage->to ?? '');
            if ($phone === '') {
                continue;
            }

            $failures[$phone] = trim((string) ($failedMessage->statusMessage ?? '')) ?: '발송 요청이 거절되었습니다.';
        }

        return count($failedMessages);
    }

    /**
     * @param  array<int, Message>  $chunk
     * @param  array<string, string>  $failures
     */
    private function recordChunkFailure(array $chunk, array &$failures, string $reason): int
    {
        foreach ($chunk as $message) {
            $phone = $this->formatPhoneNumber($message->getTo());
            if ($phone !== '') {
                $failures[$phone] = $reason !== '' ? $reason : '발송 요청이 거절되었습니다.';
            }
        }

        return count($chunk);
    }

    private function makeMessage($application, string $phone, string $kind, ?string $entryLink, ?string $verificationCode): Message
    {
        $kakaoOption = new KakaoOption();
        $kakaoOption->setPfId($this->pfId())
            ->setTemplateId($this->templateId($kind))
            ->setDisableSms(false);

        $variables = $this->variablesFor($application, $kind, $entryLink, $verificationCode);
        if ($variables !== []) {
            $kakaoOption->setVariables((object) $variables);
        }

        $message = new Message();
        $message->setTo($phone)
            ->setFrom($this->fromNumber())
            ->setKakaoOptions($kakaoOption);

        return $message;
    }

    /**
     * 템플릿에 적힌 #{변수}와 키가 같아야 합니다.
     *
     * @return array<string, string>
     */
    private function variablesFor($application, string $kind, ?string $entryLink, ?string $verificationCode): array
    {
        if ($kind === self::KIND_OTP) {
            return [
                (string) config('services.solapi.kakao_var_otp') => (string) $verificationCode,
            ];
        }

        if ($kind === self::KIND_MORNING) {
            return [];
        }

        if ($kind === self::KIND_ENTRY) {
            return [
                (string) config('services.solapi.kakao_var_entry_link') => (string) $entryLink,
            ];
        }

        return [
            (string) config('services.solapi.kakao_var_applicant_name') => (string) $application->parent_name,
            (string) config('services.solapi.kakao_var_receipt_number') => (string) $application->receipt_number,
        ];
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

    private function fromNumber(): string
    {
        return preg_replace('/[^0-9]/', '', (string) config('services.solapi.from_number')) ?? '';
    }

    private function templateId(string $kind): string
    {
        $key = match ($kind) {
            self::KIND_MORNING => 'services.solapi.kakao_template_morning',
            self::KIND_ENTRY => 'services.solapi.kakao_template_entry',
            self::KIND_OTP => 'services.solapi.kakao_template_otp',
            default => 'services.solapi.kakao_template_completed',
        };

        $templateId = trim((string) config($key));
        if ($templateId === '' && $kind === self::KIND_COMPLETED) {
            $templateId = trim((string) config('services.solapi.kakao_template_id'));
        }

        return $templateId;
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
