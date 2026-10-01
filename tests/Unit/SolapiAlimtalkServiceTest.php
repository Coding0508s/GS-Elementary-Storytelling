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
        ]);

        $this->assertFalse((new SolapiAlimtalkService())->isReady());
    }

    public function test_send_fills_template_variables_and_disables_sms_fallback(): void
    {
        config([
            'services.solapi.kakao_pf_id' => 'KA01PF',
            'services.solapi.kakao_template_id' => 'KA01TP',
            'services.solapi.kakao_var_student_name' => '#{학생이름}',
            'services.solapi.kakao_var_receipt_number' => '#{접수번호}',
        ]);

        $submission = new VideoSubmission();
        $submission->id = 12;
        $submission->student_name_korean = '김뚜비';
        $submission->parent_phone = '010-9522-0584';

        $invalid = new VideoSubmission();
        $invalid->id = 13;
        $invalid->student_name_korean = '번호없음';
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
        $this->assertNull($message->from);
        $this->assertSame('KA01PF', $message->getKakaoOptions()->getPfId());
        $this->assertSame('KA01TP', $message->getKakaoOptions()->getTemplateId());
        $this->assertTrue($message->getKakaoOptions()->getDisableSms());
        $this->assertSame('김뚜비', $variables['#{학생이름}']);
        $this->assertSame('GSK-00012', $variables['#{접수번호}']);

        $this->assertSame(1, $result['requested']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, $result['skipped']);
    }
}
