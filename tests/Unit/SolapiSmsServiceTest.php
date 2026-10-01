<?php

namespace Tests\Unit;

use App\Services\SolapiSmsService;
use Nurigo\Solapi\Models\Message;
use Nurigo\Solapi\Models\Response\SendResponse;
use Nurigo\Solapi\Services\SolapiMessageService;
use Tests\TestCase;

class SolapiSmsServiceTest extends TestCase
{
    public function test_send_sms_uses_digits_only_and_configured_sender(): void
    {
        config([
            'services.solapi.from_number' => '1544-9055',
        ]);

        $client = $this->createMock(SolapiMessageService::class);
        $client->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) {
                return $message instanceof Message
                    && $message->to === '01012345678'
                    && $message->from === '15449055'
                    && $message->text === '안녕하세요';
            }))
            ->willReturn(new SendResponse((object) [
                'groupInfo' => (object) [
                    'groupId' => 'G4V01',
                    'status' => 'SENDING',
                ],
                'failedMessageList' => [],
            ]));

        $service = new SolapiSmsService($client);
        $result = $service->sendSms('010-1234-5678', '안녕하세요');

        $this->assertTrue($result['success']);
        $this->assertSame('G4V01', $result['group_id']);
        $this->assertSame('SENDING', $result['status']);
    }

    public function test_send_sms_returns_failure_when_sender_is_missing(): void
    {
        config([
            'services.solapi.api_key' => null,
            'services.solapi.api_secret' => null,
            'services.solapi.from_number' => null,
        ]);

        $service = new SolapiSmsService();
        $result = $service->sendSms('01012345678', '테스트');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Solapi API 키 설정이 없습니다.', $result['error']);
    }
}
