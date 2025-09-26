<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3UploadController extends Controller
{
    /**
     * Presigned URL을 생성하여 클라이언트가 직접 S3에 업로드할 수 있도록 함
     */
    public function generatePresignedUrl(Request $request)
    {
        try {
            // 요청 검증
            $request->validate([
                'filename' => 'required|string|max:255',
                'content_type' => 'required|string',
                'file_size' => 'required|integer|max:2147483648', // 2GB 제한
                'institution_name' => 'nullable|string|max:255',
                'student_name_korean' => 'nullable|string|max:255',
                'grade' => 'nullable|string|max:50',
            ]);

            // Rate limiting 체크 (IP당 분당 10회 제한)
            $key = 's3_presigned_url:' . $request->ip();
            $attempts = cache()->get($key, 0);
            if ($attempts >= 10) {
                return response()->json([
                    'error' => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.'
                ], 429);
            }
            cache()->put($key, $attempts + 1, 60); // 1분간 유지

            $originalFilename = $request->input('filename');
            $contentType = $request->input('content_type');
            $fileSize = $request->input('file_size');

            // 파일명 보안 검증 (검증용 파일명)
            $filename = basename($originalFilename); // 경로 조작 방지
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename); // 특수문자 제거 (검증 목적)
            
            // 파일 확장자 검증
            $allowedExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'error' => '지원하지 않는 파일 형식입니다. (mp4, avi, mov, wmv, flv, webm, mkv만 허용)'
                ], 400);
            }

            // Content-Type 검증
            $allowedContentTypes = [
                'video/mp4', 'video/quicktime', 'video/avi', 'video/x-msvideo',
                'video/x-ms-wmv', 'video/x-flv', 'video/webm', 'video/x-matroska'
            ];
            
            if (!in_array($contentType, $allowedContentTypes)) {
                return response()->json([
                    'error' => '지원하지 않는 Content-Type입니다.'
                ], 400);
            }

            // 파일 크기 검증 (2GB 제한)
            if ($fileSize > 2147483648) {
                return response()->json([
                    'error' => '파일 크기가 너무 큽니다. 최대 2GB까지 허용됩니다.'
                ], 400);
            }

            // 사용자 정의 파일명 생성 (원본 파일명 사용)
            $customFilename = $this->generateCustomFilename(
                $request->input('institution_name'),
                $request->input('student_name_korean'),
                $request->input('grade'),
                $originalFilename
            );
            
            // 고유한 파일명 생성 (기관명_이름_학년_원본파일명_타임스탬프.확장자)
            $uniqueFilename = 'videos/' . date('Y/m/d') . '/' . $customFilename;
            
            Log::info('S3 파일명 생성', [
                'original_filename' => $originalFilename,
                'sanitized_filename' => $filename,
                'custom_filename' => $customFilename,
                'unique_filename' => $uniqueFilename,
                'institution_name' => $request->input('institution_name'),
                'student_name' => $request->input('student_name_korean'),
                'grade' => $request->input('grade')
            ]);
            
            // S3 클라이언트 생성
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
            ]);

            // Presigned URL 생성 (15분 유효)
            $command = $s3Client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $uniqueFilename,
                'ContentType' => $contentType,
                'ContentLength' => $fileSize,
                'ACL' => 'private', // 명시적 ACL 설정
            ]);

            $presignedUrl = $s3Client->createPresignedRequest($command, '+15 minutes')->getUri();

            Log::info('Presigned URL 생성됨', [
                'filename' => $filename,
                's3_key' => $uniqueFilename,
                'file_size' => $fileSize,
                'content_type' => $contentType,
            ]);

            return response()->json([
                'presigned_url' => (string) $presignedUrl,
                's3_key' => $uniqueFilename,
                's3_url' => 'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' . $uniqueFilename,
                'expires_in' => 900, // 15분
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
            ]);

        } catch (\Exception $e) {
            Log::error('Presigned URL 생성 실패', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Presigned URL 생성에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * S3 업로드 완료 후 콜백 처리
     */
    public function uploadComplete(Request $request)
    {
        try {
            $request->validate([
                's3_key' => 'required|string|max:500',
                'original_filename' => 'required|string|max:255',
                'file_size' => 'required|integer|max:2147483648',
                'content_type' => 'required|string|max:100',
            ]);

            // S3 키 보안 검증 (더 유연한 패턴)
            $s3Key = $request->input('s3_key');
            
            Log::info('S3 키 검증', [
                's3_key' => $s3Key,
                'pattern_match' => preg_match('/^videos\/\d{4}\/\d{2}\/\d{2}\/[^\/]+\.(mp4|avi|mov|wmv|flv|webm|mkv)$/', $s3Key)
            ]);
            
            if (!preg_match('/^videos\/\d{4}\/\d{2}\/\d{2}\/[^\/]+\.(mp4|avi|mov|wmv|flv|webm|mkv)$/', $s3Key)) {
                Log::error('S3 키 형식 오류', [
                    's3_key' => $s3Key,
                    'expected_pattern' => 'videos/YYYY/MM/DD/filename.extension'
                ]);
                return response()->json([
                    'error' => '유효하지 않은 S3 키 형식입니다.'
                ], 400);
            }

            $originalFilename = $request->input('original_filename');
            $fileSize = $request->input('file_size');
            $contentType = $request->input('content_type');

            // S3에서 파일 존재 확인
            if (!Storage::disk('s3')->exists($s3Key)) {
                return response()->json([
                    'error' => '업로드된 파일을 찾을 수 없습니다.'
                ], 404);
            }

            // 파일 정보 반환
            $fileInfo = [
                's3_key' => $s3Key,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'content_type' => $contentType,
                'uploaded_at' => now(),
                'url' => $this->getS3Url($s3Key),
            ];

            Log::info('S3 업로드 완료', $fileInfo);

            return response()->json([
                'success' => true,
                'file_info' => $fileInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('S3 업로드 완료 처리 실패', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => '업로드 완료 처리에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * S3 파일 삭제
     */
    public function deleteFile(Request $request)
    {
        try {
            $request->validate([
                's3_key' => 'required|string',
            ]);

            $s3Key = $request->input('s3_key');

            // S3에서 파일 삭제
            if (Storage::disk('s3')->exists($s3Key)) {
                Storage::disk('s3')->delete($s3Key);
                
                Log::info('S3 파일 삭제됨', ['s3_key' => $s3Key]);
                
                return response()->json([
                    'success' => true,
                    'message' => '파일이 성공적으로 삭제되었습니다.'
                ]);
            } else {
                return response()->json([
                    'error' => '삭제할 파일을 찾을 수 없습니다.'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('S3 파일 삭제 실패', [
                'error' => $e->getMessage(),
                's3_key' => $request->input('s3_key'),
            ]);

            return response()->json([
                'error' => '파일 삭제에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * S3 파일 URL 생성
     */
    public function getFileUrl(Request $request)
    {
        try {
            $request->validate([
                's3_key' => 'required|string',
            ]);

            $s3Key = $request->input('s3_key');

            // 파일 존재 확인
            if (!Storage::disk('s3')->exists($s3Key)) {
                return response()->json([
                    'error' => '파일을 찾을 수 없습니다.'
                ], 404);
            }

            // 임시 URL 생성 (1시간 유효)
            $url = $this->getS3TemporaryUrl($s3Key);

            return response()->json([
                'url' => $url,
                'expires_at' => now()->addHour()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('S3 파일 URL 생성 실패', [
                'error' => $e->getMessage(),
                's3_key' => $request->input('s3_key'),
            ]);

            return response()->json([
                'error' => '파일 URL 생성에 실패했습니다.'
            ], 500);
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
     * S3 임시 URL 생성 헬퍼 메서드
     */
    private function getS3TemporaryUrl($s3Key)
    {
        try {
            return Storage::disk('s3')->temporaryUrl($s3Key, now()->addHour());
        } catch (\Exception $e) {
            Log::warning('S3 임시 URL 생성 실패, 일반 URL 사용', ['s3_key' => $s3Key, 'error' => $e->getMessage()]);
            return $this->getS3Url($s3Key);
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
