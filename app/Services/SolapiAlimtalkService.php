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

    public function adminConfigurationHash(string $kind): string
    {
        if (! in_array($kind, [self::KIND_MORNING, self::KIND_ENTRY], true)
            || ! $this->isReady($kind) || $this->fromNumber() === ''
            || ! config('services.solapi.api_key') || ! config('services.solapi.api_secret')) {
            throw new Exception('알림톡 템플릿, 발신번호와 API 설정을 확인해주세요.');
        }

        return hash('sha256', json_encode([
            $kind, $this->pfId(), $this->templateId($kind), $this->fromNumber(),
            config('services.solapi.kakao_var_entry_link'),
        ], JSON_THROW_ON_ERROR));
    }

    /** Common administrator notices only. Completed/OTP callers keep their existing semantics. */
    public function sendAdminBatch(array $phones, string $kind, ?string $entryLink): array
    {
        return $this->deliverAdminBatch($phones, $kind, $entryLink, null);
    }

    public function scheduleAdminBatch(array $phones, string $kind, ?string $entryLink, \DateTimeInterface $when): array
    {
        if ($when->getTimestamp() < now()->timestamp + 60) {
            return array_fill_keys($phones, ['status' => 'skipped', 'reason' => '예약 시간까지 1분 미만으로 남아 즉시 전송을 방지했습니다.']);
        }

        return $this->deliverAdminBatch($phones, $kind, $entryLink, \DateTime::createFromInterface($when));
    }

    private function deliverAdminBatch(array $phones, string $kind, ?string $entryLink, ?\DateTime $when): array
    {
        $this->adminConfigurationHash($kind);
        if (count($phones) > 100 || count(array_unique($phones)) !== count($phones)) {
            throw new Exception('한 번에 서로 다른 번호 100개까지 보낼 수 있습니다.');
        }
        if ($kind === self::KIND_ENTRY) {
            $entryLink = $this->entryLinkValue((string) $entryLink);
        }
        $messages = [];
        foreach ($phones as $phone) {
            if ($phone !== $this->formatPhoneNumber($phone) || ! $this->isMobilePhone($phone)) {
                throw new Exception('올바르지 않은 수신 번호입니다.');
            }
            $messages[] = $this->makeMessage(null, $phone, $kind, $entryLink, null);
        }
        if ($messages === []) {
            return [];
        }

        $results = array_fill_keys($phones, ['status' => 'unknown', 'reason' => '접수 여부를 확인하지 못했습니다.']);
        try {
            $response = $this->client()->send($messages, $when);
            if (empty($response->groupInfo->groupId)) {
                return $results;
            }
            $failures = [];
            foreach ($response->failedMessageList ?? [] as $failure) {
                $phone = $this->formatPhoneNumber($failure->to ?? '');
                if (! in_array($phone, $phones, true)) {
                    return $results; // Unmappable response: never infer successful acceptance.
                }
                if (isset($failures[$phone])) {
                    return $results;
                }
                $failures[$phone] = $failure->statusMessage ?? '발송 요청이 거절되었습니다.';
            }
            foreach ($phones as $phone) {
                $results[$phone] = [
                    'status' => isset($failures[$phone]) ? 'failed' : ($when ? ($response->groupInfo->status === 'SCHEDULED' ? 'scheduled' : 'unknown') : 'accepted'),
                    'reason' => $failures[$phone] ?? null,
                    'provider_group_id' => $response->groupInfo->groupId,
                ];
            }
        } catch (MessageNotReceivedException $e) {
            foreach ($e->getFailedMessageList() as $failure) {
                $phone = $this->formatPhoneNumber($failure->to ?? '');
                if (isset($results[$phone])) {
                    $results[$phone] = ['status' => 'failed', 'reason' => $failure->statusMessage ?? '발송 요청이 거절되었습니다.'];
                }
            }
        } catch (\Throwable $e) {
            // The provider may have accepted the request before the connection was lost.
            Log::warning('관리자 알림톡 접수 여부 확인 필요', ['exception' => get_class($e)]);
        }

        return $results;
    }

    /** The SDK has no cancellation method; use the documented group API, without retries. */
    public function manageAdminSchedule(string $groupId, bool $cancel): array
    {
        $date = gmdate('Y-m-d\TH:i:s\Z');
        $salt = bin2hex(random_bytes(16));
        $key = (string) config('services.solapi.api_key');
        $signature = hash_hmac('sha256', $date.$salt, (string) config('services.solapi.api_secret'));
        $url = 'https://api.solapi.com/messages/v4/groups/'.rawurlencode($groupId);
        $request = \Illuminate\Support\Facades\Http::connectTimeout(5)->timeout(15)->withHeaders([
            'Authorization' => "HMAC-SHA256 apiKey={$key}, date={$date}, salt={$salt}, signature={$signature}",
        ]);
        try {
            $response = $cancel ? $request->delete($url.'/schedule') : $request->get($url);
            $group = $response->json();
            if (! $response->successful() || ($group['groupId'] ?? null) !== $groupId) {
                return ['status' => $cancel ? 'cancel_unknown' : 'unavailable', 'reason' => '제공사 예약 상태를 확인하지 못했습니다. 다시 상태를 조회해주세요.'];
            }
            $status = match ($group['status'] ?? '') {
                'PENDING', 'DELETED' => 'cancelled',
                'SCHEDULED' => $cancel ? 'cancel_unknown' : 'scheduled',
                'SENDING', 'PROCESSING', 'COMPLETE' => 'accepted',
                'FAILED' => 'failed',
                default => $cancel ? 'cancel_unknown' : 'unavailable',
            };

            return ['status' => $status, 'reason' => match ($status) {
                'cancel_unknown' => '예약 취소 여부를 확인하지 못했습니다. 제공사 기록을 확인해주세요.',
                'accepted' => $cancel ? '이미 발송 처리가 시작되어 취소하지 못했습니다.' : null,
                'failed' => '제공사에서 발송 실패로 처리되었습니다.',
                default => null,
            }];
        } catch (\Throwable $e) {
            return ['status' => $cancel ? 'cancel_unknown' : 'unavailable', 'reason' => '제공사와 연결하지 못했습니다. 예약이 취소되었다고 가정하지 마세요.'];
        }
    }

    public function normalizedAdminPhone(string $phone): string
    {
        return $this->formatPhoneNumber($phone);
    }

    /** Only reads results. Never infer delivery from the group's COMPLETE status. */
    public function adminDeliveryResults(string $groupId): array
    {
        $messages = [];
        $startKey = null;
        $seen = [];
        // Administrator batches contain at most 100 numbers. Bound both pagination and time.
        for ($page = 0; $page < 2; $page++) {
            $date = gmdate('Y-m-d\TH:i:s\Z');
            $salt = bin2hex(random_bytes(16));
            $key = (string) config('services.solapi.api_key');
            $signature = hash_hmac('sha256', $date.$salt, (string) config('services.solapi.api_secret'));
            if ($key === '' || ! config('services.solapi.api_secret')) {
                throw new Exception('SOLAPI 조회 설정을 확인해주세요.');
            }
            $response = \Illuminate\Support\Facades\Http::connectTimeout(3)->timeout(8)->withHeaders([
                'Authorization' => "HMAC-SHA256 apiKey={$key}, date={$date}, salt={$salt}, signature={$signature}",
            ])->get('https://api.solapi.com/messages/v4/groups/'.rawurlencode($groupId).'/messages',
                array_filter(['limit' => 500, 'startKey' => $startKey], fn ($value) => $value !== null));
            $body = $response->json();
            if (! $response->successful() || ! is_array($body['messageList'] ?? null)) {
                throw new Exception('SOLAPI 발송 결과를 조회하지 못했습니다.');
            }
            foreach ($body['messageList'] as $message) {
                if (! is_array($message) || ($message['groupId'] ?? null) !== $groupId
                    || empty($message['messageId']) || isset($seen[$message['messageId']])) {
                    throw new Exception('SOLAPI 발송 결과의 연결 정보를 확인하지 못했습니다.');
                }
                $seen[$message['messageId']] = true;
                $phone = $this->formatPhoneNumber((string) ($message['to'] ?? ''));
                if (! $this->isMobilePhone($phone) || (string) ($message['country'] ?? '82') !== '82') {
                    continue;
                }
                $messages[$phone][] = $message;
            }
            $nextKey = $body['nextKey'] ?? null;
            if (! $nextKey) {
                return array_map(fn ($items) => $this->deliveryResult($items), $messages);
            }
            if (! is_string($nextKey) || $nextKey === $startKey) {
                break;
            }
            $startKey = $nextKey;
        }
        throw new Exception('SOLAPI 결과의 모든 페이지를 확인하지 못했습니다. 다시 조회해주세요.');
    }

    private function deliveryResult(array $messages): array
    {
        // Some providers return a separate replacement message, others update the original.
        // Prefer the replacement's FINAL result; an earlier Kakao failure is not final.
        $replacements = array_values(array_filter($messages, fn ($item) => ($item['replacement'] ?? false) === true));
        $candidates = $replacements ?: $messages;
        if (count($candidates) !== 1) {
            return ['status' => 'unavailable', 'reason' => '같은 번호의 메시지가 여러 개여서 최종 결과를 확인하지 못했습니다.'];
        }
        $message = $candidates[0];
        $code = (string) ($message['statusCode'] ?? '');
        $complete = ($message['status'] ?? null) === 'COMPLETE';
        $status = match (true) {
            $complete && $code === '4000' => 'delivered',
            $complete && $code === '1070' => 'cancelled',
            $complete && preg_match('/^[1-5][0-9]{3}$/', $code) && ! in_array($code, ['2000', '3000', '4000'], true) => 'delivery_failed',
            $code === '3000' => 'delivering',
            $code === '2000' => 'accepted',
            // A channel failure while the replacement is queued is not a delivery failure.
            ! $complete && preg_match('/^[1-5][0-9]{3}$/', $code) => 'delivering',
            default => 'unavailable',
        };
        $type = (string) ($message['type'] ?? '');

        return [
            'status' => $status,
            'reason' => $status === 'delivery_failed' ? (string) ($message['reason'] ?? $message['statusMessage'] ?? '제공사에서 전달 실패로 처리했습니다.') : null,
            'provider_status_code' => $code,
            'delivery_channel' => in_array($type, ['ATA', 'CTA', 'CTI', 'SMS', 'LMS', 'MMS'], true) ? $type : null,
        ];
    }

    public function validAdminPhone(string $phone): bool
    {
        return $this->isMobilePhone($phone);
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
        $kakaoOption = new KakaoOption;
        $kakaoOption->setPfId($this->pfId())
            ->setTemplateId($this->templateId($kind))
            ->setDisableSms(false);

        $variables = $this->variablesFor($application, $kind, $entryLink, $verificationCode);
        if ($variables !== []) {
            $kakaoOption->setVariables((object) $variables);
        }

        $message = new Message;
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
