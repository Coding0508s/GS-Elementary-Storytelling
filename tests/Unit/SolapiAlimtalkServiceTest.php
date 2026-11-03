<?php

namespace Tests\Unit;

use App\Models\VideoSubmission;
use App\Services\SolapiAlimtalkService;
use Illuminate\Support\Collection;
use Nurigo\Solapi\Models\Message;
use Nurigo\Solapi\Models\Response\SendResponse;
use Nurigo\Solapi\Services\SolapiMessageService;
use Tests\TestCase;

class SolapiAlimtalkServiceTest extends TestCase
{
    public function test_not_ready_without_template(): void
    {
        config([
            'services.solapi.kakao_pf_id' => '',
            'services.solapi.kakao_template_id' => '',
            'services.solapi.kakao_template_completed' => '',
        ]);

        $this->assertFalse((new SolapiAlimtalkService)->isReady());
    }

    public function test_send_fills_template_variables_and_allows_sms_fallback(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_id' => 'KA01TP',
            'services.solapi.kakao_template_completed' => '',
            'services.solapi.from_number' => '1544-9055',
            'services.solapi.kakao_var_applicant_name' => '#{신청자명}',
            'services.solapi.kakao_var_receipt_number' => '#{접수번호}',
        ]);

        $submission = new VideoSubmission;
        $submission->id = 12;
        $submission->student_name_korean = '김뚜비';
        $submission->parent_name = '김학부모';
        $submission->parent_phone = '010-9522-0584';

        $invalid = new VideoSubmission;
        $invalid->id = 13;
        $invalid->student_name_korean = '번호없음';
        $invalid->parent_name = '번호학부모';
        $invalid->parent_phone = '02-123-4567';

        $client = new class extends SolapiMessageService
        {
            public $messages;

            public function __construct() {}

            public function send($messages, $scheduledDateTime = null): SendResponse
            {
                $this->messages = $messages;

                return new SendResponse((object) [
                    'groupInfo' => (object) ['groupId' => 'G1', 'status' => 'SENDING'],
                    'failedMessageList' => [],
                ]);
            }
        };

        $result = (new SolapiAlimtalkService($client))->sendToApplications(new Collection([$submission, $invalid]));

        $message = $client->messages[0];
        $variables = (array) $message->getKakaoOptions()->getVariables();
        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('01095220584', $message->to);
        $this->assertSame('15449055', $message->from);
        $this->assertSame('KA01PF', $message->getKakaoOptions()->getPfId());
        $this->assertSame('KA01TP', $message->getKakaoOptions()->getTemplateId());
        $this->assertFalse($message->getKakaoOptions()->getDisableSms());
        $this->assertSame('김학부모', $variables['#{신청자명}']);
        $this->assertSame('GSK-00012', $variables['#{접수번호}']);

        $this->assertSame(1, $result['requested']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('sent', $result['results'][0]['status']);
        $this->assertSame('skipped', $result['results'][1]['status']);
        $this->assertSame('휴대폰 번호가 아닙니다.', $result['results'][1]['reason']);
    }

    public function test_send_marks_rejected_recipient_as_failed(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_morning' => 'KA01MORNING',
            'services.solapi.from_number' => '15449055',
        ]);

        $submission = new VideoSubmission;
        $submission->id = 3;
        $submission->student_name_korean = '김뚜비';
        $submission->parent_name = '김학부모';
        $submission->parent_phone = '01012345678';

        $client = new class extends SolapiMessageService
        {
            public function __construct() {}

            public function send($messages, $scheduledDateTime = null): SendResponse
            {
                return new SendResponse((object) [
                    'groupInfo' => (object) ['groupId' => 'G1', 'status' => 'SENDING'],
                    'failedMessageList' => [
                        (object) ['to' => '01012345678', 'statusMessage' => '템플릿 불일치'],
                    ],
                ]);
            }
        };

        $result = (new SolapiAlimtalkService($client))->sendToApplications(
            new Collection([$submission]),
            SolapiAlimtalkService::KIND_MORNING
        );

        $this->assertSame(0, $result['requested']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame('failed', $result['results'][0]['status']);
        $this->assertSame('템플릿 불일치', $result['results'][0]['reason']);
        $this->assertSame('GSK-00003', $result['results'][0]['receipt']);
    }

    public function test_morning_message_has_no_variables(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_morning' => 'KA01MORNING',
            'services.solapi.from_number' => '15449055',
        ]);

        $submission = new VideoSubmission;
        $submission->parent_phone = '01012345678';

        $client = $this->capturingClient();
        (new SolapiAlimtalkService($client))->sendToApplications(
            new Collection([$submission]),
            SolapiAlimtalkService::KIND_MORNING
        );

        $message = $client->messages[0];
        $options = $message->getKakaoOptions();
        $this->assertSame('KA01MORNING', $options->getTemplateId());
        $this->assertNull($options->getVariables());
        $this->assertFalse($options->getDisableSms());
        $this->assertSame('15449055', $message->from);
    }

    public function test_entry_message_falls_back_to_sms_when_kakao_fails(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_entry' => 'KA01ENTRY',
            'services.solapi.kakao_var_entry_link' => '#{입장링크}',
            'services.solapi.from_number' => '1544-9055',
        ]);

        $submission = new VideoSubmission;
        $submission->parent_phone = '01012345678';

        $client = $this->capturingClient();
        (new SolapiAlimtalkService($client))->sendToApplications(
            new Collection([$submission]),
            SolapiAlimtalkService::KIND_ENTRY,
            'https://www.youtube.com/watch?v=abc123'
        );

        $message = $client->messages[0];
        $variables = (array) $message->getKakaoOptions()->getVariables();
        $this->assertFalse($message->getKakaoOptions()->getDisableSms());
        $this->assertSame('15449055', $message->from);
        $this->assertSame('www.youtube.com/watch?v=abc123', $variables['#{입장링크}']);
    }

    public function test_verification_code_uses_otp_template_variable(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_otp' => 'KA01OTP',
            'services.solapi.kakao_var_otp' => '#{인증번호}',
            'services.solapi.from_number' => '15449055',
        ]);

        $client = $this->capturingClient();
        $result = (new SolapiAlimtalkService($client))->sendVerificationCode('010-1234-5678', '482915');

        $message = $client->messages[0];
        $variables = (array) $message->getKakaoOptions()->getVariables();
        $this->assertSame('KA01OTP', $message->getKakaoOptions()->getTemplateId());
        $this->assertSame('482915', $variables['#{인증번호}']);
        $this->assertFalse($message->getKakaoOptions()->getDisableSms());
        $this->assertSame('15449055', $message->from);
        $this->assertSame(1, $result['requested']);
    }

    public function test_entry_link_drops_https_and_rejects_non_youtube_urls(): void
    {
        $service = new SolapiAlimtalkService;

        $this->assertSame(
            'www.youtube.com/watch?v=abc123',
            $service->entryLinkValue('https://www.youtube.com/watch?v=abc123')
        );
        $this->assertSame(
            'youtu.be/abc123',
            $service->entryLinkValue('https://youtu.be/abc123')
        );

        $this->expectException(\Exception::class);
        $service->entryLinkValue('https://us02web.zoom.us/j/12345678901');
    }

    public function test_admin_batch_records_partial_rejection_and_provider_identifier(): void
    {
        $service = $this->adminService(fn () => new SendResponse((object) [
            'groupInfo' => (object) ['groupId' => 'G-admin'],
            'failedMessageList' => [(object) ['to' => '01022222222', 'statusMessage' => '거절']],
        ]));
        $result = $service->sendAdminBatch(['01012345678', '01022222222'], 'morning', null);
        $this->assertSame('accepted', $result['01012345678']['status']);
        $this->assertSame('failed', $result['01022222222']['status']);
        $this->assertSame('G-admin', $result['01012345678']['provider_group_id']);
    }

    public function test_admin_connection_loss_is_unknown_instead_of_retryable_failure(): void
    {
        $service = $this->adminService(function () {
            throw new \RuntimeException('response lost after acceptance');
        });
        $this->assertSame('unknown', $service->sendAdminBatch(['01012345678'], 'morning', null)['01012345678']['status']);
    }

    public function test_admin_unmappable_or_empty_responses_never_infer_acceptance(): void
    {
        foreach ([
            (object) [],
            (object) ['groupInfo' => (object) ['groupId' => '']],
            (object) ['groupInfo' => (object) ['groupId' => 'G1'], 'failedMessageList' => [(object) ['to' => '01099999999']]],
        ] as $response) {
            $service = $this->adminService(fn () => new SendResponse($response));
            $this->assertSame('unknown', $service->sendAdminBatch(['01012345678'], 'morning', null)['01012345678']['status']);
        }
    }

    public function test_explicit_provider_exception_marks_only_identified_rejections(): void
    {
        $service = $this->adminService(function () {
            throw new \Nurigo\Solapi\Exceptions\MessageNotReceivedException([
                (object) ['to' => '01012345678', 'statusMessage' => '템플릿 불일치'],
            ]);
        });
        $result = $service->sendAdminBatch(['01012345678', '01022222222'], 'morning', null);
        $this->assertSame('failed', $result['01012345678']['status']);
        $this->assertSame('unknown', $result['01022222222']['status']);
    }

    public function test_admin_path_rejects_duplicate_numbers_before_calling_provider(): void
    {
        $service = $this->adminService(function () {
            $this->fail('Provider should not be called');
        });
        $this->expectException(\Exception::class);
        $service->sendAdminBatch(['01012345678', '01012345678'], 'morning', null);
    }

    public function test_admin_path_rejects_non_admin_templates(): void
    {
        $service = $this->adminService(function () {
            $this->fail('Provider should not be called');
        });
        $this->expectException(\Exception::class);
        $service->sendAdminBatch(['01012345678'], 'completed', null);
    }

    public function test_native_reservation_passes_exact_time_and_requires_scheduled_provider_response(): void
    {
        $when = now()->addHour();
        $service = $this->adminService(function ($messages, $date) use ($when) {
            $this->assertSame($when->format('c'), $date->format('c'));

            return new SendResponse((object) ['groupInfo' => (object) ['groupId' => 'G1', 'status' => 'SCHEDULED']]);
        });
        $this->assertSame('scheduled', $service->scheduleAdminBatch(['01012345678'], 'morning', null, $when)['01012345678']['status']);
        $service = $this->adminService(fn () => new SendResponse((object) ['groupInfo' => (object) ['groupId' => 'G1', 'status' => 'SENDING']]));
        $this->assertSame('unknown', $service->scheduleAdminBatch(['01012345678'], 'morning', null, $when)['01012345678']['status']);
    }

    public function test_native_reservation_never_sends_a_past_date_to_provider(): void
    {
        $service = $this->adminService(function () {
            $this->fail('Past date must not be sent');
        });
        $this->assertSame('skipped', $service->scheduleAdminBatch(['01012345678'], 'morning', null, now()->subMinute())['01012345678']['status']);
    }

    public function test_native_cancel_uses_signed_delete_and_validates_provider_state(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Http::fake(['api.solapi.com/*' => \Illuminate\Support\Facades\Http::sequence()->push(['groupId' => 'G1', 'status' => 'PENDING'])->push(['groupId' => 'G1', 'status' => 'SENDING'])]);
        $service = new SolapiAlimtalkService;
        $this->assertSame('cancelled', $service->manageAdminSchedule('G1', true)['status']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->method() === 'DELETE'
            && $r->url() === 'https://api.solapi.com/messages/v4/groups/G1/schedule'
            && str_starts_with($r->header('Authorization')[0], 'HMAC-SHA256 '));
        $this->assertSame('accepted', $service->manageAdminSchedule('G1', true)['status']);
    }

    public function test_native_uncertain_cancellation_and_status_read_never_report_cancelled(): void
    {
        \Illuminate\Support\Facades\Http::fake(['api.solapi.com/*' => \Illuminate\Support\Facades\Http::response([], 500)]);
        $service = new SolapiAlimtalkService;
        $this->assertSame('cancel_unknown', $service->manageAdminSchedule('G1', true)['status']);
        $this->assertSame('unavailable', $service->manageAdminSchedule('G1', false)['status']);
    }

    public function test_delivery_query_classifies_final_and_pending_results_and_signs_get_only(): void
    {
        $service = $this->adminService(fn () => throw new \RuntimeException('Must not send'));
        $base = ['groupId' => 'G-results', 'country' => '82', 'type' => 'ATA', 'replacement' => false];
        $messages = [];
        foreach ([['2000', 'PENDING'], ['3000', 'SENDING'], ['4000', 'COMPLETE'], ['3043', 'COMPLETE'], ['3104', 'SENDING'], ['1070', 'COMPLETE'], ['N000', 'COMPLETE']] as $i => [$code, $state]) {
            $messages['M'.$i] = $base + ['messageId' => 'M'.$i, 'to' => '0101234567'.$i, 'status' => $state, 'statusCode' => $code, 'reason' => '전달 사유'];
        }
        \Illuminate\Support\Facades\Http::fake(['api.solapi.com/*' => \Illuminate\Support\Facades\Http::response(['messageList' => $messages])]);
        $results = $service->adminDeliveryResults('G-results');
        foreach (['accepted', 'delivering', 'delivered', 'delivery_failed', 'delivering', 'cancelled', 'unavailable'] as $i => $status) {
            $this->assertSame($status, $results['0101234567'.$i]['status']);
        }
        $this->assertSame('전달 사유', $results['01012345673']['reason']);
        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $authorization = $request->header('Authorization')[0];
            preg_match('/date=([^,]+), salt=([^,]+), signature=(.+)$/', $authorization, $parts);

            return $request->method() === 'GET' && str_contains($request->url(), '/groups/G-results/messages')
                && str_contains($request->url(), 'limit=500')
                && hash_equals(hash_hmac('sha256', $parts[1].$parts[2], 'test-secret'), $parts[3]);
        });
        \Illuminate\Support\Facades\Http::assertSentCount(1);
    }

    public function test_delivery_query_waits_for_replacement_and_reports_sms_success_separately(): void
    {
        $service = $this->adminService(fn () => throw new \RuntimeException('Must not send'));
        $original = ['groupId' => 'G-replace', 'to' => '01012345678', 'statusCode' => '3104', 'status' => 'COMPLETE', 'type' => 'ATA', 'messageId' => 'M1'];
        $replacement = ['groupId' => 'G-replace', 'to' => '01012345678', 'statusCode' => '2000', 'status' => 'PENDING', 'type' => 'SMS', 'messageId' => 'M2', 'replacement' => true];
        \Illuminate\Support\Facades\Http::fakeSequence()->push(['messageList' => [$original, $replacement]])
            ->push(['messageList' => [$original, array_replace($replacement, ['statusCode' => '4000', 'status' => 'COMPLETE'])]])
            ->push(['messageList' => [$original, array_replace($replacement, ['statusCode' => '3040', 'status' => 'COMPLETE', 'reason' => '문자 시간 초과'])]]);
        $this->assertSame('accepted', $service->adminDeliveryResults('G-replace')['01012345678']['status']);
        $result = $service->adminDeliveryResults('G-replace')['01012345678'];
        $this->assertSame('delivered', $result['status']);
        $this->assertSame('SMS', $result['delivery_channel']);
        $this->assertSame('delivery_failed', $service->adminDeliveryResults('G-replace')['01012345678']['status']);
    }

    public function test_delivery_query_follows_next_key_and_rejects_incomplete_or_foreign_results(): void
    {
        $service = $this->adminService(fn () => throw new \RuntimeException('Must not send'));
        $message = ['messageId' => 'M1', 'groupId' => 'G-pages', 'to' => '01012345678', 'status' => 'COMPLETE', 'statusCode' => '4000'];
        \Illuminate\Support\Facades\Http::fakeSequence()->push(['messageList' => [], 'nextKey' => 'cursor-a'])
            ->push(['messageList' => [$message]]);
        $this->assertSame('delivered', $service->adminDeliveryResults('G-pages')['01012345678']['status']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => str_contains($request->url(), 'startKey=cursor-a'));
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory);
        \Illuminate\Support\Facades\Http::fakeSequence()->push(['messageList' => [$message], 'nextKey' => 'cursor-a'])
            ->push(['messageList' => [], 'nextKey' => 'cursor-a']);
        try {
            $service->adminDeliveryResults('G-pages');
            $this->fail('Incomplete pagination must not produce success.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('모든 페이지', $e->getMessage());
        }
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['messageList' => [array_replace($message, ['groupId' => 'G-other'])]])]);
        $this->expectExceptionMessage('연결 정보');
        $service->adminDeliveryResults('G-pages');
    }

    public function test_delivery_query_failure_never_infers_delivery_from_http_or_group_completion(): void
    {
        $service = $this->adminService(fn () => throw new \RuntimeException('Must not send'));
        foreach ([['groupId' => 'G1', 'status' => 'COMPLETE'], ['messageList' => null], ['errorCode' => 'Forbidden']] as $body) {
            \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response($body)]);
            try {
                $service->adminDeliveryResults('G1');
                $this->fail('No individual results must not produce delivery success.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('조회하지 못했습니다', $e->getMessage());
            }
        }
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::failedConnection()]);
        $this->expectException(\Illuminate\Http\Client\ConnectionException::class);
        $service->adminDeliveryResults('G1');
    }

    private function adminService(\Closure $respond): SolapiAlimtalkService
    {
        config([
            'services.solapi.api_key' => 'test-key', 'services.solapi.api_secret' => 'test-secret',
            'services.solapi.kakao_pf_id' => 'PF', 'services.solapi.kakao_template_morning' => 'M',
            'services.solapi.from_number' => '15449055',
        ]);

        return new SolapiAlimtalkService(new class($respond) extends SolapiMessageService
        {
            public function __construct(private \Closure $respond) {}

            public function send($messages, $scheduledDateTime = null): SendResponse
            {
                return ($this->respond)($messages, $scheduledDateTime);
            }
        });
    }

    private function capturingClient(): SolapiMessageService
    {
        return new class extends SolapiMessageService
        {
            public $messages;

            public function __construct() {}

            public function send($messages, $scheduledDateTime = null): SendResponse
            {
                $this->messages = $messages;

                return new SendResponse((object) [
                    'groupInfo' => (object) ['groupId' => 'G1', 'status' => 'SENDING'],
                    'failedMessageList' => [],
                ]);
            }
        };
    }
}
