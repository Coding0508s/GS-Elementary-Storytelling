<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitoringMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // 요청 처리
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // 밀리초
        
        // 모니터링 데이터 수집
        $this->collectMetrics($request, $response, $responseTime);
        
        return $response;
    }

    /**
     * 모니터링 메트릭 수집
     */
    private function collectMetrics(Request $request, $response, $responseTime)
    {
        try {
            // 총 요청 수 증가
            $this->incrementCounter('storytelling:total_requests');
            
            // 응답 시간 기록
            $this->recordResponseTime($responseTime);
            
            // 오류 요청 추적
            if ($response->getStatusCode() >= 400) {
                $this->incrementCounter('storytelling:error_requests');
                $this->recordErrorType($response->getStatusCode());
            }
            
            // Rate Limiting 추적
            if ($response->getStatusCode() === 429 || $response->getStatusCode() === 503) {
                $this->incrementCounter('storytelling:rate_limit_hits');
            }
            
            // S3 업로드 관련 추적
            if ($this->isS3UploadRequest($request)) {
                $this->incrementCounter('storytelling:total_uploads');
                
                if ($response->getStatusCode() === 200) {
                    $this->incrementCounter('storytelling:successful_uploads');
                }
            }
            
            // 동시 접속자 수 업데이트
            $this->updateActiveConnections($request);
            
        } catch (\Exception $e) {
            Log::error('모니터링 데이터 수집 실패', [
                'error' => $e->getMessage(),
                'request_uri' => $request->getRequestUri()
            ]);
        }
    }

    /**
     * 카운터 증가
     */
    private function incrementCounter($key)
    {
        try {
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, 3600); // 1시간 유지
        } catch (\Exception $e) {
            // 캐시 오류는 무시
        }
    }

    /**
     * 응답 시간 기록
     */
    private function recordResponseTime($responseTime)
    {
        try {
            $responseTimes = Cache::get('storytelling:response_times', []);
            $responseTimes[] = round($responseTime, 2);
            
            // 최대 1000개 데이터 포인트 유지
            if (count($responseTimes) > 1000) {
                $responseTimes = array_slice($responseTimes, -1000);
            }
            
            Cache::put('storytelling:response_times', $responseTimes, 3600);
        } catch (\Exception $e) {
            // 캐시 오류는 무시
        }
    }

    /**
     * 오류 유형 기록
     */
    private function recordErrorType($statusCode)
    {
        try {
            $errorTypes = Cache::get('storytelling:error_types', []);
            $errorType = $this->getErrorTypeName($statusCode);
            
            if (!isset($errorTypes[$errorType])) {
                $errorTypes[$errorType] = 0;
            }
            
            $errorTypes[$errorType]++;
            Cache::put('storytelling:error_types', $errorTypes, 3600);
        } catch (\Exception $e) {
            // 캐시 오류는 무시
        }
    }

    /**
     * 오류 유형명 반환
     */
    private function getErrorTypeName($statusCode)
    {
        switch ($statusCode) {
            case 400:
                return 'Bad Request';
            case 401:
                return 'Unauthorized';
            case 403:
                return 'Forbidden';
            case 404:
                return 'Not Found';
            case 429:
                return 'Too Many Requests';
            case 500:
                return 'Internal Server Error';
            case 503:
                return 'Service Unavailable';
            default:
                return "HTTP {$statusCode}";
        }
    }

    /**
     * S3 업로드 요청인지 확인
     */
    private function isS3UploadRequest(Request $request)
    {
        $uri = $request->getRequestUri();
        return strpos($uri, '/api/s3/') !== false || 
               strpos($uri, '/upload') !== false;
    }

    /**
     * 활성 연결 수 업데이트
     */
    private function updateActiveConnections(Request $request)
    {
        try {
            $sessionId = $request->session()->getId();
            $cacheKey = "storytelling:active_session:{$sessionId}";
            
            // 세션 활성 상태로 마킹 (30분 유지)
            Cache::put($cacheKey, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => now()->timestamp
            ], 1800);
            
            // 활성 세션 수 계산
            $this->updateActiveConnectionsCount();
            
        } catch (\Exception $e) {
            // 세션 오류는 무시
        }
    }

    /**
     * 활성 연결 수 계산
     */
    private function updateActiveConnectionsCount()
    {
        try {
            // 캐시에서 활성 세션 수 계산
            $activeCount = 0;
            $cacheKeys = Cache::get('storytelling:active_sessions', []);
            
            foreach ($cacheKeys as $sessionId) {
                $sessionData = Cache::get("storytelling:active_session:{$sessionId}");
                if ($sessionData && $sessionData['last_activity'] > now()->subMinutes(30)->timestamp) {
                    $activeCount++;
                }
            }
            
            Cache::put('storytelling:active_connections', $activeCount, 60);
            
        } catch (\Exception $e) {
            // 계산 오류는 무시
        }
    }
}
