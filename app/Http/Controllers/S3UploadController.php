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

            // 업로드 전 OTP 검증 여부 확인 (세션)
            if (!session('otp_verified') || session('otp_verified') !== true) {
                return response()->json([
                    'error' => '휴대폰 인증이 필요합니다. 인증 후 다시 시도해주세요.'
                ], 403);
            }

            // 개선된 Rate limiting (동시 접속자 고려)
            $ipKey = 'storytelling:s3_presigned_url:ip:' . $request->ip();
            $globalKey = 'storytelling:s3_presigned_url:global';
            
            // 적응형 Rate Limiting (서버 부하에 따른 동적 조절)
            $serverLoad = $this->getServerLoad();
            $adaptiveLimits = $this->getAdaptiveLimits($serverLoad);
            
            // IP별 제한 (적응형)
            $ipAttempts = cache()->get($ipKey, 0);
            if ($ipAttempts >= $adaptiveLimits['ip']) {
                return response()->json([
                    'error' => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',
                    'retry_after' => 60,
                    'limit_type' => 'ip'
                ], 429);
            }
            
            // 전역 동시 요청 제한 (적응형)
            $globalAttempts = cache()->get($globalKey, 0);
            if ($globalAttempts >= $adaptiveLimits['global']) {
                return response()->json([
                    'error' => '서버가 바쁩니다. 잠시 후 다시 시도해주세요.',
                    'retry_after' => 60,
                    'limit_type' => 'global'
                ], 503);
            }
            
            // 카운터 증가 (일관된 시간 단위 사용)
            cache()->put($ipKey, $ipAttempts + 1, 60); // 1분간 유지
            cache()->put($globalKey, $globalAttempts + 1, 60); // 1분간 유지
            
            // 동시 접속 모니터링 로깅
            Log::info('S3 Presigned URL 요청', [
                'ip' => $request->ip(),
                'ip_attempts' => $ipAttempts + 1,
                'global_attempts' => $globalAttempts + 1,
                'timestamp' => now()
            ]);

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
                    'error' => '지원하지 않는 파일 형식입니다. (mp4,mov만 허용)'
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
                    'error' => '파일 크기가 너무 큽니다. 최대 1GB까지 허용됩니다.'
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
            
            // 로깅 최소화로 성능 향상
            
            // 최적화된 S3 클라이언트 생성 (연결 풀링 및 재사용)
            $s3Client = $this->getOptimizedS3Client();

            // Presigned URL 생성 (15분 유효)
            $command = $s3Client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $uniqueFilename,
                'ContentType' => $contentType,
                'ContentLength' => $fileSize,
                'ACL' => 'private', // 명시적 ACL 설정
            ]);

            $presignedUrl = $s3Client->createPresignedRequest($command, '+15 minutes')->getUri();

            // 성공 로깅 생략

            return response()->json([
                'presigned_url' => (string) $presignedUrl,
                's3_key' => $uniqueFilename,
                's3_url' => 'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' . $uniqueFilename,
            ]);

        } catch (\Aws\Exception\AwsException $e) {
            // AWS 특정 오류 처리
            Log::error('AWS Presigned URL 생성 실패', [
                'aws_error_code' => $e->getAwsErrorCode(),
                'aws_error_message' => $e->getAwsErrorMessage(),
                'status_code' => $e->getStatusCode(),
                'request_id' => $e->getAwsRequestId(),
            ]);

            // 동시 접속으로 인한 일시적 오류인 경우 재시도 안내
            if (in_array($e->getAwsErrorCode(), ['Throttling', 'RequestLimitExceeded', 'ServiceUnavailable'])) {
                return response()->json([
                    'error' => '3초 후 다시 시도해주세요.',
                    'retry_after' => 3
                ], 503);
            }
             //AWS 서비스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.
            return response()->json([
                'error' => '잠시 후 다시 시도해주세요.'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Presigned URL 생성 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => ' 브라우저 새로 고침 후 다시 시도해주세요.'
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
            
            // S3 키 형식 검증 (로깅 최소화)
            if (!preg_match('/^videos\/\d{4}\/\d{2}\/\d{2}\/[^\/]+\.(mp4|avi|mov|wmv|flv|webm|mkv)$/', $s3Key)) {
                return response()->json(['error' => '유효하지 않은 S3 키 형식'], 400);
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
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            return 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . ltrim($s3Key, '/');
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
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
            ]);
            $command = $s3Client->getCommand('GetObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $s3Key,
            ]);
            $request = $s3Client->createPresignedRequest($command, '+60 minutes');
            return (string) $request->getUri();
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

        // 타임스탬프 추가 (중복 방지 - 마이크로초 포함)
        $timestamp = date('Ymd_His') . '_' . substr(microtime(), 2, 6);

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
     * 최적화된 S3 클라이언트 생성 (동시 접속 최적화)
     */
    private function getOptimizedS3Client()
    {
        static $s3Client = null;
        
        // 싱글톤 패턴으로 클라이언트 재사용
        if ($s3Client === null) {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'http' => [
                    // 업로드 속도 최적화 설정 (강화)
                    'timeout' => 1800,       // 30분 (대용량 파일 대응 강화)
                    'connect_timeout' => 30, // 30초 (연결 속도 최적화)
                    'pool_size' => 2000,     // 2000 (동시 연결 풀 대폭 확대)
                    'verify' => true,
                    'stream' => true,        // 스트리밍 업로드 활성화
                    'decode_content' => false, // 디코딩 비활성화로 속도 향상
                ],
                'retries' => [
                    'mode' => 'adaptive',   // 적응형 재시도
                    'max_attempts' => 5,     // 재시도 횟수 증가
                ],
                'use_accelerate_endpoint' => false, // S3 Transfer Acceleration 비활성화 (CORS 문제 해결)
                'use_dual_stack_endpoint' => false, // IPv6 비활성화 (CORS 문제 해결)
                'use_path_style_endpoint' => false, // 가상 호스팅 스타일
            ]);
        }
        
        return $s3Client;
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
    
    /**
     * 서버 부하 상태 확인
     */
    private function getServerLoad()
    {
        // CPU 사용률 확인
        $cpuUsage = sys_getloadavg()[0] ?? 0;
        
        // 메모리 사용률 확인
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        // 활성 연결 수 확인
        $activeConnections = $this->getActiveConnections();
        
        return [
            'cpu' => $cpuUsage,
            'memory_percent' => $memoryPercent,
            'active_connections' => $activeConnections,
            'load_level' => $this->determineLoadLevel($cpuUsage, $memoryPercent, $activeConnections)
        ];
    }
    
    /**
     * 적응형 제한값 계산
     */
    private function getAdaptiveLimits($serverLoad)
    {
        $loadLevel = $serverLoad['load_level'];
        
        switch ($loadLevel) {
            case 'low':
                return [
                    'ip' => 100,      // IP당 100회/분
                    'global' => 1000   // 전역 1000회/분
                ];
            case 'medium':
                return [
                    'ip' => 50,       // IP당 50회/분
                    'global' => 500    // 전역 500회/분
                ];
            case 'high':
                return [
                    'ip' => 30,       // IP당 30회/분
                    'global' => 300    // 전역 300회/분
                ];
            case 'critical':
                return [
                    'ip' => 15,       // IP당 15회/분
                    'global' => 150    // 전역 150회/분
                ];
            default:
                return [
                    'ip' => 50,       // 기본값
                    'global' => 500
                ];
        }
    }
    
    /**
     * 부하 수준 결정
     */
    private function determineLoadLevel($cpuUsage, $memoryPercent, $activeConnections)
    {
        if ($cpuUsage > 4 || $memoryPercent > 90 || $activeConnections > 200) {
            return 'critical';
        } elseif ($cpuUsage > 2 || $memoryPercent > 75 || $activeConnections > 100) {
            return 'high';
        } elseif ($cpuUsage > 1 || $memoryPercent > 50 || $activeConnections > 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * 활성 연결 수 확인
     */
    private function getActiveConnections()
    {
        // 캐시에서 활성 연결 수 확인
        $cacheKey = 'storytelling:active_connections';
        return cache()->get($cacheKey, 0);
    }
    
    /**
     * 메모리 제한 파싱
     */
    private function parseMemoryLimit($memoryLimit)
    {
        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) $memoryLimit;
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }
}
