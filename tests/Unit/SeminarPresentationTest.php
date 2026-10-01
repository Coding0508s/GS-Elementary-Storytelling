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
        $this->assertStringNotContainsString('웨비나 신청이 완료되었습니다', $html);
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
        $this->assertStringContainsString('웨비나 신청이 완료되었습니다', $html);
        $this->assertStringContainsString($submission->receipt_number, $html);
        $this->assertStringContainsString('문자 수신이 지연되더라도 신청은 접수되었습니다', $html);
        $this->assertStringContainsString('웨비나 링크는 행사 시작 30분 전에 위에 입력하신 전화번호로 전송됩니다.', $html);
        $this->assertStringNotContainsString('다른 자녀 신청하기', $html);
        $this->assertStringNotContainsString('SMS 알림 발송 완료', $html);
    }

    public function test_consent_describes_the_fields_collected_for_the_seminar(): void
    {
        $html = view('privacy-consent', ['errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('웨비나 신청 접수', $html);
        $this->assertStringContainsString('180일간 보관', $html);
        $this->assertStringContainsString('참석 일자', $html);
        $this->assertStringContainsString('마케팅 정보 수신 동의 (선택)', $html);
        $this->assertStringContainsString('동의하지 않아도 웨비나 신청에 아무런 제한이 없습니다.', $html);
        $this->assertStringContainsString('강사님께 궁금한 점 (선택 입력)', $html);
        $this->assertStringNotContainsString('제출영상', $html);
        $this->assertStringNotContainsString('초상권', $html);
    }

    public function test_attendance_choice_shows_only_the_selected_instructor_question(): void
    {
        $submission = new VideoSubmission();
        $submission->attendance_day = 'day1';
        $submission->question_day1 = 'Day 1 질문';
        $submission->question_day2 = '숨겨진 질문';

        $this->assertSame('Day 1', $submission->attendanceLabel());
        $this->assertSame('Day 1 질문', $submission->instructorQuestion('day1'));
        $this->assertSame('숨겨진 질문', $submission->instructorQuestion('day2'));

        $html = view('upload-form', ['errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('name="attendance_day"', $html);
        $this->assertStringContainsString('김상균 교수님', $html);
        $this->assertStringContainsString('윤윤구 강사님', $html);
        $this->assertStringContainsString('id="instructor-questions"', $html);
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
        $this->assertStringContainsString('웨비나 신청이 완료되었습니다', $service->capturedMessage);
        $this->assertStringContainsString('GSK-00042', $service->capturedMessage);
        $this->assertStringNotContainsString('영상 업로드', $service->capturedMessage);

        $submission->video_file_path = 'videos/test.mp4';
        $service->sendUploadCompletionNotification($submission);
        $this->assertStringContainsString('영상 업로드 완료', $service->capturedMessage);
    }
}
