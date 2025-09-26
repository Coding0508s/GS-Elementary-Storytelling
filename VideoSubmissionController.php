<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VideoSubmission;
use App\Services\NotificationService;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoSubmissionController extends Controller
{
    /**
     * 이벤트 소개 페이지 표시
     */
    public function showEventIntro()
    {
        return view('event-intro');
    }

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
                        ->with('success', '개인정보 수집 및 이용에 동의해주셔서 감사합니다. 이제 영상을 업로드할 수 있습니다.');
    }

    /**
     * 영상 업로드 처리
     */
    public function uploadVideo(Request $request)
    {
        // Force PHP upload settings for large files (2GB)
        ini_set('upload_max_filesize', '2048M');
        ini_set('post_max_size', '2048M');
        ini_set('max_execution_time', '0'); // 무제한
        set_time_limit(0); // 추가 시간 제한 제거
        ini_set('max_input_time', '3600');
        ini_set('memory_limit', '1024M');
        $validator = Validator::make($request->all(), [
            'region' => ['required', 'string', function ($attribute, $value, $fail) {
                // "시/도 시/군/구" 형태인지 확인
                $parts = explode(' ', $value, 2);
                if (count($parts) < 2) {
                    $fail('올바른 지역 형식을 선택해주세요.');
                    return;
                }
                
                $province = $parts[0];
                $city = $parts[1];
                
                // 시/도가 유효한지 확인
                if (!array_key_exists($province, VideoSubmission::REGIONS)) {
                    $fail('올바른 시/도를 선택해주세요.');
                    return;
                }
                
                // 시/군/구가 해당 시/도에 속하는지 확인
                if (!in_array($city, VideoSubmission::REGIONS[$province])) {
                    $fail('올바른 시/군/구를 선택해주세요.');
                    return;
                }
            }],
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
                'max:2097152' // 2GB in KB (2048 * 1024)
            ]
        ], [
            'region.required' => '거주 지역을 선택해주세요.',
            'institution_name.required' => '기관명을 입력해주세요.',
            'class_name.required' => '반 이름을 입력해주세요.',
            'student_name_korean.required' => '학생 한글 이름을 입력해주세요.',
            'student_name_english.required' => '학생 영어 이름을 입력해주세요.',
            'grade.required' => '학년을 입력해주세요.',
            'age.required' => '나이를 입력해주세요.',
            'age.integer' => '나이는 숫자로 입력해주세요.',
            'parent_name.required' => '학부모 성함을 입력해주세요.',
            'parent_phone.required' => '학부모 전화번호를 입력해주세요.',
            'video_file.required' => '영상 파일을 선택해주세요.',
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
            
            // 파일을 S3에 저장
            $filePath = $videoFile->storeAs('videos', $fileName, 's3');
            
            // S3에 저장된 파일의 URL 생성
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

            // Supabase 설정이 없으므로 비활성화
            // $this->saveToSupabase($submission);

            // Twilio SMS 알림 전송
            $this->sendSmsNotification($submission);

            // 세션 정리
            $request->session()->forget(['privacy_consent', 'privacy_consent_time']);

            return redirect()->route('upload.success')
                           ->with('success', '영상이 성공적으로 업로드되었습니다. 곧 SMS 알림을 받으실 수 있습니다.')
                           ->with('submission_id', $submission->id);

        } catch (\Exception $e) {
            return back()->with('error', '업로드 중 오류가 발생했습니다: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * 기관명 자동완성을 위한 API
     */
    public function getInstitutions(Request $request)
    {
        $query = $request->get('q', '');
        
        // Institution 테이블에서 활성화된 기관명만 조회
        $institutionsQuery = \App\Models\Institution::active()->ordered();
        
        // 검색어가 있을 때는 필터링
        if (strlen($query) >= 1) {
            $institutionsQuery->search($query);
            $limit = 10;
        } else {
            // 전체 목록일 때는 더 많이 표시
            $limit = 20;
        }
        
        $institutions = $institutionsQuery->limit($limit)->pluck('name');
            
        return response()->json($institutions);
    }

    /**
     * 업로드 성공 페이지 표시
     */
    public function showUploadSuccess(Request $request)
    {
        $submission = null;
        
        // 세션에서 submission_id 가져오기
        if ($request->session()->has('submission_id')) {
            $submissionId = $request->session()->get('submission_id');
            $submission = VideoSubmission::find($submissionId);
            
            // 보안을 위해 세션에서 ID 제거
            $request->session()->forget('submission_id');
        }
        
        return view('upload-success', compact('submission'));
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

    /**
     * Twilio SMS 알림 전송
     */
    private function sendSmsNotification($submission)
    {
        try {
            $twilioService = new TwilioSmsService();
            $result = $twilioService->sendUploadCompletionNotification($submission);
            
            if ($result['success']) {
                Log::info('SMS 알림 전송 성공', [
                    'submission_id' => $submission->id,
                    'phone' => $submission->parent_phone,
                    'message_sid' => $result['message_sid']
                ]);
            } else {
                Log::error('SMS 알림 전송 실패', [
                    'submission_id' => $submission->id,
                    'phone' => $submission->parent_phone,
                    'error' => $result['error']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS 알림 전송 중 오류 발생', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
        }
    }


}
