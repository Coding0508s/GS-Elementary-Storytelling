<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VideoSubmission;
use App\Services\NotificationService;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Institution;
use App\Jobs\SendSmsJob;

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
     * 비디오 업로드 처리 (S3 직접 업로드 지원)
     */
    public function uploadVideo(Request $request)
    {
        // S3 직접 업로드인지 확인
        $isS3DirectUpload = $request->has('s3_key') && $request->has('s3_url');
        
        if ($isS3DirectUpload) {
            // S3 직접 업로드 처리
            return $this->handleS3DirectUpload($request);
        } else {
            // 기존 서버 업로드 처리
            return $this->handleServerUpload($request);
        }
    }

    /**
     * S3 직접 업로드 처리
     */
    private function handleS3DirectUpload(Request $request)
    {
        $startTime = microtime(true);
        Log::info('S3 Direct Upload - Process started.');

        $validator = Validator::make($request->all(), [
            'region' => ['required', 'string', function ($attribute, $value, $fail) {
                $parts = explode(' ', $value, 2);
                if (count($parts) < 2) {
                    $fail('올바른 지역 형식을 선택해주세요.');
                    return;
                }
                
                $province = $parts[0];
                $city = $parts[1];
                
                if (!array_key_exists($province, VideoSubmission::REGIONS)) {
                    $fail('올바른 시/도를 선택해주세요.');
                    return;
                }
                
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
            's3_key' => 'required|string',
            's3_url' => 'required|string' // URL 검증을 완화
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
            's3_key.required' => 'S3 파일 키가 필요합니다.',
            's3_url.required' => 'S3 파일 URL이 필요합니다.'
        ]);

        $validationTime = microtime(true);
        Log::info('S3 Direct Upload - Validation finished.', ['duration_ms' => ($validationTime - $startTime) * 1000]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '입력 데이터가 유효하지 않습니다.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // S3 키에서 파일명 추출 (파일 존재 확인 생략으로 성능 최적화)
            $s3Key = $request->s3_key;
            $fileName = basename($s3Key);
            
            // 파일 크기 정보 추출 (업로드 완료 콜백에서 전달받은 정보 사용)
            $fileSize = $request->input('file_size', 0);
            $contentType = $request->input('content_type', 'video/quicktime');
            
            $dataExtractionTime = microtime(true);
            Log::info('S3 Direct Upload - Data extraction finished.', [
                'duration_ms' => ($dataExtractionTime - $validationTime) * 1000,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);
            
            // 데이터베이스에 정보 저장 (최적화된 필드만)
            $submission = new VideoSubmission();
            $submission->fill([
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
                'video_file_path' => $s3Key,
                'video_file_name' => $fileName,
                'video_file_type' => $contentType,
                'video_file_size' => $fileSize,
                'video_url' => $request->s3_url,
                'upload_method' => 's3_direct',
                'privacy_consent' => true,
                'privacy_consent_at' => now(),
                'status' => VideoSubmission::STATUS_UPLOADED
            ]);
            $submission->save();

            $dbSaveTime = microtime(true);
            Log::info('S3 Direct Upload - Database save finished.', ['duration_ms' => ($dbSaveTime - $validationTime) * 1000]);

            // 세션에 submission_id 저장
            session(['submission_id' => $submission->id]);

            $sessionSaveTime = microtime(true);
            Log::info('S3 Direct Upload - Session save finished.', ['duration_ms' => ($sessionSaveTime - $dbSaveTime) * 1000]);

            // ⚡ SMS 알림 발송 (설정에 따라 동기식 또는 Queue)
            $smsStartTime = microtime(true);
            try {
                // Twilio 설정이 있는지 확인
                if (config('services.twilio.account_sid')) {
                    // 환경변수로 SMS 발송 방식 제어 (기본값: 동기식)
                    $useSyncSms = env('SMS_SYNC_MODE', true);
                    
                    if ($useSyncSms) {
                        // 동기식 즉시 발송
                        $twilioService = app(\App\Services\TwilioSmsService::class);
                        $smsResult = $twilioService->sendUploadCompletionNotification($submission);
                        
                        if ($smsResult['success']) {
                            Log::info('SMS 즉시 발송 성공', [
                                'submission_id' => $submission->id,
                                'phone' => $submission->parent_phone,
                                'message_sid' => $smsResult['message_sid']
                            ]);
                        } else {
                            Log::error('SMS 즉시 발송 실패', [
                                'submission_id' => $submission->id,
                                'error' => $smsResult['error'] ?? 'Unknown error'
                            ]);
                        }
                    } else {
                        // Queue 방식 (fallback)
                        \App\Jobs\SendSmsJob::dispatch($submission);
                        Log::info('SMS Queue에 추가됨', [
                            'submission_id' => $submission->id
                        ]);
                    }
                } else {
                    Log::info('Twilio 설정이 없어 SMS 발송 건너뜀', [
                        'submission_id' => $submission->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SMS 발송 예외 발생', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // SMS 발송 실패해도 업로드는 계속 진행
            }
            $smsEndTime = microtime(true);
            
            Log::info('S3 Direct Upload - SMS 발송 완료', [
                'duration_ms' => ($smsEndTime - $smsStartTime) * 1000
            ]);

            $totalTime = microtime(true);
            Log::info('S3 Direct Upload - Process finished before response.', [
                'total_duration_ms' => ($totalTime - $startTime) * 1000,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'breakdown' => [
                    'validation_ms' => ($validationTime - $startTime) * 1000,
                    'data_extraction_ms' => ($dataExtractionTime - $validationTime) * 1000,
                    'database_save_ms' => ($dbSaveTime - $dataExtractionTime) * 1000,
                    'session_save_ms' => ($sessionSaveTime - $dbSaveTime) * 1000,
                    'sms_dispatch_ms' => ($smsEndTime - $smsStartTime) * 1000
                ]
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => route('upload.success')
            ]);

        } catch (\Exception $e) {
            Log::error('S3 직접 업로드 처리 실패', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['s3_key', 's3_url'])
            ]);

            return response()->json([
                'success' => false,
                'message' => '업로드 처리 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 기존 서버 업로드 처리 (하위 호환성)
     */
    private function handleServerUpload(Request $request)
    {
        // Force PHP upload settings for large files (2GB)
        ini_set('upload_max_filesize', '2048M');
        ini_set('post_max_size', '2048M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        ini_set('max_input_time', '3600');
        ini_set('memory_limit', '1024M');
        
        $validator = Validator::make($request->all(), [
            'region' => ['required', 'string', function ($attribute, $value, $fail) {
                $parts = explode(' ', $value, 2);
                if (count($parts) < 2) {
                    $fail('올바른 지역 형식을 선택해주세요.');
                    return;
                }
                
                $province = $parts[0];
                $city = $parts[1];
                
                if (!array_key_exists($province, VideoSubmission::REGIONS)) {
                    $fail('올바른 시/도를 선택해주세요.');
                    return;
                }
                
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
                'mimes:mp4,mov,avi,wmv,flv,webm,mkv',
                'max:2097152' // 2GB in KB
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
            'video_file.mimes' => '지원하지 않는 파일 형식입니다. (MP4, AVI, MOV, WMV, FLV, WEBM, MKV만 허용)',
            'video_file.max' => '파일 크기는 1GB를 초과할 수 없습니다.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $videoFile = $request->file('video_file');
            
            // 사용자 정의 파일명 생성
            $fileName = $this->generateCustomFilename(
                $request->institution_name,
                $request->student_name_korean,
                $request->grade,
                $videoFile->getClientOriginalName()
            );
            
            // 파일을 S3에 저장
            $filePath = $videoFile->storeAs('videos', $fileName, 's3');
            
            // S3에 저장된 파일의 URL 생성
            $fileUrl = $this->getS3Url($filePath);

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

    /**
     * S3 URL 생성 헬퍼 메서드
     */
    private function getS3Url($s3Key)
    {
        try {
            return Storage::disk('s3')->url($s3Key);
        } catch (\Exception $e) {
            Log::warning('S3 URL 생성 실패', ['s3_key' => $s3Key, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * 사용자 정의 파일명 생성
     * 형식: 기관명_이름_학년_원본파일명_타임스탬프.확장자
     */
    private function generateCustomFilename($institutionName, $studentName, $grade, $originalFilename)
    {
        // 원본 파일명에서 확장자를 먼저 추출
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $baseOriginalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);

        // 안전한 파일명을 위해 특수문자 제거 및 공백을 언더스코어로 변경
        $safeInstitution = $this->sanitizeFilename($institutionName ?? 'Unknown');
        $safeStudentName = $this->sanitizeFilename($studentName ?? 'Unknown');
        $safeGrade = $this->sanitizeFilename($grade ?? 'Unknown');
        $safeOriginalName = $this->sanitizeFilename($baseOriginalFilename ?? 'video');

        // 타임스탬프 추가 (중복 방지)
        $timestamp = date('Ymd_His');

        // 확장자가 없으면 기본값으로 mp4 설정
        if (empty($extension)) {
            $extension = 'mp4';
        }

        // 최종 파일명 생성
        $customFilename = sprintf(
            '%s_%s_%s_%s_%s.%s',
            $safeInstitution,
            $safeStudentName,
            $safeGrade,
            $safeOriginalName,
            $timestamp,
            $extension
        );
        
        // 파일명 길이 제한 (S3 키 길이 제한 고려)
        if (strlen($customFilename) > 200) {
            $customFilename = substr($customFilename, 0, 200) . '.' . $extension;
        }
        
        return $customFilename;
    }

    /**
     * 파일명 안전화 (특수문자 제거, 공백 보존)
     */
    private function sanitizeFilename($filename)
    {
        // 한글, 영문, 숫자, 공백, 언더스코어, 하이픈만 허용
        $filename = preg_replace('/[^가-힣a-zA-Z0-9 _-]/', '_', $filename);
        
        // 연속된 공백을 하나로 변경
        $filename = preg_replace('/\s+/', ' ', $filename);
        
        // 연속된 언더스코어를 하나로 변경
        $filename = preg_replace('/_+/', '_', $filename);
        
        // 앞뒤 공백과 언더스코어 제거
        $filename = trim($filename, ' _');
        
        // 빈 문자열인 경우 기본값
        if (empty($filename)) {
            $filename = 'Unknown';
        }
        
        return $filename;
    }
}
