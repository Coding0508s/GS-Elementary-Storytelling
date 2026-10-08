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

        $this->assertFalse((new SolapiAlimtalkService())->isReady());
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

        $submission = new VideoSubmission();
        $submission->id = 12;
        $submission->student_name_korean = '김뚜비';
        $submission->parent_name = '김학부모';
        $submission->parent_phone = '010-9522-0584';

        $invalid = new VideoSubmission();
        $invalid->id = 13;
        $invalid->student_name_korean = '번호없음';
        $invalid->parent_name = '번호학부모';
        $invalid->parent_phone = '02-123-4567';

        $client = new class extends SolapiMessageService
        {
            public $messages;

            public function __construct()
            {
            }

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
    }

    public function test_morning_message_has_no_variables(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_morning' => 'KA01MORNING',
            'services.solapi.from_number' => '15449055',
        ]);

        $submission = new VideoSubmission();
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

        $submission = new VideoSubmission();
        $submission->parent_phone = '01012345678';

        $client = $this->capturingClient();
        (new SolapiAlimtalkService($client))->sendToApplications(
            new Collection([$submission]),
            SolapiAlimtalkService::KIND_ENTRY,
            'https://us02web.zoom.us/j/12345678901?pwd=abc'
        );

        $message = $client->messages[0];
        $variables = (array) $message->getKakaoOptions()->getVariables();
        $this->assertFalse($message->getKakaoOptions()->getDisableSms());
        $this->assertSame('15449055', $message->from);
        $this->assertSame('us02web.zoom.us/j/12345678901?pwd=abc', $variables['#{입장링크}']);
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

    public function test_entry_link_drops_https_and_rejects_non_zoom_urls(): void
    {
        $service = new SolapiAlimtalkService();

        $this->assertSame(
            'us02web.zoom.us/j/12345678901?pwd=abc',
            $service->entryLinkValue('https://us02web.zoom.us/j/12345678901?pwd=abc')
        );

        $this->expectException(\Exception::class);
        $service->entryLinkValue('https://grapeseed.ac/webinar');
    }

    private function capturingClient(): SolapiMessageService
    {
        return new class extends SolapiMessageService
        {
            public $messages;

            public function __construct()
            {
            }

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
