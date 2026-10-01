<?php

namespace Tests\Unit;

use App\Models\VideoSubmission;
use App\Services\SolapiSmsService;
use Tests\TestCase;

class SeminarPresentationTest extends TestCase
{
    public function test_completion_without_receipt_does_not_claim_success(): void
    {
        $html = view('upload-success', ['submission' => null, 'errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('접수 정보를 확인할 수 없습니다', $html);
        $this->assertStringNotContainsString('세미나 신청이 완료되었습니다', $html);
        $this->assertStringNotContainsString('SMS 알림 발송 완료', $html);
    }

    public function test_completed_application_shows_receipt_without_claiming_sms_delivery(): void
    {
        $submission = new VideoSubmission();
        $submission->id = 42;
        $submission->created_at = '2026-10-01 15:00:00';
        $html = view('upload-success', [
            'submission' => $submission,
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();
        $this->assertStringContainsString('세미나 신청이 완료되었습니다', $html);
        $this->assertStringContainsString($submission->receipt_number, $html);
        $this->assertStringContainsString('문자 수신이 지연되더라도 신청은 접수되었습니다', $html);
        $this->assertStringNotContainsString('SMS 알림 발송 완료', $html);
    }

    public function test_consent_describes_the_fields_collected_for_the_seminar(): void
    {
        $html = view('privacy-consent', ['errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('세미나 신청 접수', $html);
        $this->assertStringContainsString('강사님께 궁금한 점 (선택 입력)', $html);
        $this->assertStringNotContainsString('제출영상', $html);
        $this->assertStringNotContainsString('초상권', $html);
    }

    public function test_application_and_video_notifications_describe_the_correct_action(): void
    {
        $service = new class extends SolapiSmsService {
            public string $capturedMessage = '';
            public function sendSms($to, $message)
            {
                $this->capturedMessage = $message;
                return ['success' => true];
            }
        };
        $submission = new VideoSubmission();
        $submission->id = 42;
        $submission->student_name_korean = '테스트';
        $submission->parent_phone = '01000000000';
        $service->sendUploadCompletionNotification($submission);
        $this->assertStringContainsString('세미나 신청이 완료되었습니다', $service->capturedMessage);
        $this->assertStringContainsString('GSK-00042', $service->capturedMessage);
        $this->assertStringNotContainsString('영상 업로드', $service->capturedMessage);

        $submission->video_file_path = 'videos/test.mp4';
        $service->sendUploadCompletionNotification($submission);
        $this->assertStringContainsString('영상 업로드 완료', $service->capturedMessage);
    }
}
