<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VideoSubmission;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoSubmissionController extends Controller
{
    /**
     * 개인정보 수집 동의 페이지 표시
     */
    public function showPrivacyConsent()
    {
        return view('privacy-consent');
    }

    /**
     * 업로드 폼 페이지 표시
     */
    public function showUploadForm(Request $request)
    {
        // 개인정보 동의 확인
        if (!$request->session()->has('privacy_consent') || !$request->session()->get('privacy_consent')) {
            return redirect()->route('privacy.consent')
                           ->with('error', '개인정보 수집 및 이용에 동의해야 업로드가 가능합니다.');
        }

        return view('upload-form');
    }

    /**
     * 개인정보 동의 처리
     */
    public function processPrivacyConsent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'privacy_consent' => 'required|accepted'
        ], [
            'privacy_consent.required' => '개인정보 수집 및 이용에 동의해야 합니다.',
            'privacy_consent.accepted' => '개인정보 수집 및 이용에 동의해야 합니다.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 세션에 동의 정보 저장
        $request->session()->put('privacy_consent', true);
        $request->session()->put('privacy_consent_time', now());

        return redirect()->route('upload.form')
                        ->with('success', '개인정보 수집 및 이용에 동의해주셔서 감사합니다. 이제 비디오를 업로드할 수 있습니다.');
    }

    /**
     * 비디오 업로드 처리
     */
    public function uploadVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'region' => 'required|string|max:255',
            'institution_name' => 'required|string|max:255',
            'class_name' => 'required|string|max:255',
            'student_name_korean' => 'required|string|max:255',
            'student_name_english' => 'required|string|max:255',
            'grade' => 'required|string|max:50',
            'age' => 'required|integer|min:1|max:100',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20',
            'unit_topic' => 'nullable|string|max:255',
            'video_file' => [
                'required',
                'file',
                'mimes:mp4,mov',
                'max:2097152' // 2GB in KB (2 * 1024 * 1024)
            ]
        ], [
            'region.required' => '거주 지역을 입력해주세요.',
            'institution_name.required' => '기관명을 입력해주세요.',
            'class_name.required' => '반 이름을 입력해주세요.',
            'student_name_korean.required' => '학생 한글 이름을 입력해주세요.',
            'student_name_english.required' => '학생 영어 이름을 입력해주세요.',
            'grade.required' => '학년을 입력해주세요.',
            'age.required' => '나이를 입력해주세요.',
            'age.integer' => '나이는 숫자로 입력해주세요.',
            'parent_name.required' => '학부모 성함을 입력해주세요.',
            'parent_phone.required' => '학부모 전화번호를 입력해주세요.',
            'video_file.required' => '비디오 파일을 선택해주세요.',
            'video_file.mimes' => 'MP4 또는 MOV 형식의 파일만 업로드 가능합니다.',
            'video_file.max' => '파일 크기는 2GB를 초과할 수 없습니다.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $videoFile = $request->file('video_file');
            
            // 고유한 파일명 생성
            $fileName = time() . '_' . Str::random(10) . '.' . $videoFile->getClientOriginalExtension();
            
            // 파일을 AWS S3에 저장
            $filePath = $videoFile->storeAs('videos', $fileName, 's3');
            
            // S3에 저장된 파일의 전체 URL 생성
            $fileUrl = Storage::disk('s3')->url($filePath);

            // 데이터베이스에 정보 저장
            $submission = VideoSubmission::create([
                'region' => $request->region,
                'institution_name' => $request->institution_name,
                'class_name' => $request->class_name,
                'student_name_korean' => $request->student_name_korean,
                'student_name_english' => $request->student_name_english,
                'grade' => $request->grade,
                'age' => $request->age,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'unit_topic' => $request->unit_topic,
                'video_file_path' => $filePath,  // S3 파일 경로
                'video_file_name' => $videoFile->getClientOriginalName(),
                'video_file_type' => $videoFile->getClientOriginalExtension(),
                'video_file_size' => $videoFile->getSize(),
                'privacy_consent' => true,
                'privacy_consent_at' => $request->session()->get('privacy_consent_time'),
                'status' => VideoSubmission::STATUS_UPLOADED
            ]);

            // Supabase에 데이터 저장
            $this->saveToSupabase($submission);

            // 비동기로 알림 발송 (여기서는 동기 처리)
            $this->sendNotification($submission);

            // 세션 정리
            $request->session()->forget(['privacy_consent', 'privacy_consent_time']);

            return redirect()->route('upload.success')
                           ->with('success', '비디오가 성공적으로 업로드되었습니다. 곧 알림을 받으실 수 있습니다.')
                           ->with('submission_id', $submission->id);

        } catch (\Exception $e) {
            return back()->with('error', '업로드 중 오류가 발생했습니다: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * 업로드 성공 페이지 표시
     */
    public function showUploadSuccess()
    {
        return view('upload-success');
    }

    /**
     * Supabase에 데이터 저장
     */
    private function saveToSupabase($submission)
    {
        try {
            $supabaseUrl = env('SUPABASE_URL');
            $supabaseKey = env('SUPABASE_ANON_KEY');

            if (!$supabaseUrl || !$supabaseKey) {
                throw new \Exception('Supabase 설정이 누락되었습니다.');
            }

            $response = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ])->post($supabaseUrl . '/rest/v1/video_submissions', [
                'region' => $submission->region,
                'institution_name' => $submission->institution_name,
                'class_name' => $submission->class_name,
                'student_name_korean' => $submission->student_name_korean,
                'student_name_english' => $submission->student_name_english,
                'grade' => $submission->grade,
                'age' => $submission->age,
                'parent_name' => $submission->parent_name,
                'parent_phone' => $submission->parent_phone,
                'unit_topic' => $submission->unit_topic,
                'video_file_path' => $submission->video_file_path,
                'video_file_name' => $submission->video_file_name,
                'video_file_type' => $submission->video_file_type,
                'video_file_size' => $submission->video_file_size,
                'privacy_consent' => $submission->privacy_consent,
                'privacy_consent_at' => $submission->privacy_consent_at,
                'status' => $submission->status,
                'created_at' => $submission->created_at,
                'updated_at' => $submission->updated_at
            ]);

            if (!$response->successful()) {
                throw new \Exception('Supabase 저장 실패: ' . $response->body());
            }

        } catch (\Exception $e) {
            // 로그 기록 (실제 운영에서는 로깅 시스템 사용)
            Log::error('Supabase 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * 알림 발송
     */
    private function sendNotification($submission)
    {
        try {
            $notificationService = new NotificationService();
            $notificationService->sendUploadCompletionNotification($submission);
        } catch (\Exception $e) {
            Log::error('알림 발송 오류: ' . $e->getMessage());
        }
    }
}
