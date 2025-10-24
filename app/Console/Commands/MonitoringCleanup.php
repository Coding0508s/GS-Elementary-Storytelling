<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitoringCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:cleanup {--force : 강제 실행}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '모니터링 데이터 정리 및 최적화';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('모니터링 데이터 정리 시작...');
        
        try {
            // 오래된 세션 정리
            $this->cleanupOldSessions();
            
            // 캐시 데이터 최적화
            $this->optimizeCacheData();
            
            // 로그 파일 정리
            $this->cleanupLogFiles();
            
            // 데이터베이스 최적화
            $this->optimizeDatabase();
            
            $this->info('모니터링 데이터 정리 완료!');
            
        } catch (\Exception $e) {
            $this->error('모니터링 데이터 정리 실패: ' . $e->getMessage());
            Log::error('모니터링 데이터 정리 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 오래된 세션 정리
     */
    private function cleanupOldSessions()
    {
        $this->info('오래된 세션 정리 중...');
        
        try {
            // 30분 이상 비활성 세션 삭제
            $deletedSessions = DB::table('sessions')
                ->where('last_activity', '<', now()->subMinutes(30)->timestamp)
                ->delete();
            
            $this->info("삭제된 세션 수: {$deletedSessions}");
            
            // 캐시에서도 오래된 세션 데이터 정리
            $this->cleanupCacheSessions();
            
        } catch (\Exception $e) {
            $this->error('세션 정리 실패: ' . $e->getMessage());
        }
    }

    /**
     * 캐시 세션 정리
     */
    private function cleanupCacheSessions()
    {
        try {
            $activeSessions = Cache::get('storytelling:active_sessions', []);
            $cleanedSessions = [];
            
            foreach ($activeSessions as $sessionId) {
                $sessionData = Cache::get("storytelling:active_session:{$sessionId}");
                
                if ($sessionData && $sessionData['last_activity'] > now()->subMinutes(30)->timestamp) {
                    $cleanedSessions[] = $sessionId;
                } else {
                    // 오래된 세션 캐시 삭제
                    Cache::forget("storytelling:active_session:{$sessionId}");
                }
            }
            
            Cache::put('storytelling:active_sessions', $cleanedSessions, 3600);
            
        } catch (\Exception $e) {
            $this->error('캐시 세션 정리 실패: ' . $e->getMessage());
        }
    }

    /**
     * 캐시 데이터 최적화
     */
    private function optimizeCacheData()
    {
        $this->info('캐시 데이터 최적화 중...');
        
        try {
            // 응답 시간 데이터 최적화 (최근 1000개만 유지)
            $responseTimes = Cache::get('storytelling:response_times', []);
            if (count($responseTimes) > 1000) {
                $responseTimes = array_slice($responseTimes, -1000);
                Cache::put('storytelling:response_times', $responseTimes, 3600);
            }
            
            // 오류 유형 데이터 최적화
            $errorTypes = Cache::get('storytelling:error_types', []);
            if (count($errorTypes) > 50) {
                // 상위 50개 오류 유형만 유지
                arsort($errorTypes);
                $errorTypes = array_slice($errorTypes, 0, 50, true);
                Cache::put('storytelling:error_types', $errorTypes, 3600);
            }
            
            $this->info('캐시 데이터 최적화 완료');
            
        } catch (\Exception $e) {
            $this->error('캐시 데이터 최적화 실패: ' . $e->getMessage());
        }
    }

    /**
     * 로그 파일 정리
     */
    private function cleanupLogFiles()
    {
        $this->info('로그 파일 정리 중...');
        
        try {
            $logPath = storage_path('logs');
            $maxLogSize = 100 * 1024 * 1024; // 100MB
            
            // Laravel 로그 파일 정리
            $laravelLog = $logPath . '/laravel.log';
            if (file_exists($laravelLog) && filesize($laravelLog) > $maxLogSize) {
                // 로그 파일 압축
                $compressedLog = $laravelLog . '.' . date('Y-m-d-H-i-s') . '.gz';
                $this->compressFile($laravelLog, $compressedLog);
                
                // 원본 로그 파일 삭제
                unlink($laravelLog);
                
                $this->info("로그 파일 압축 완료: {$compressedLog}");
            }
            
            // 오래된 압축 로그 파일 삭제 (7일 이상)
            $this->cleanupOldCompressedLogs($logPath);
            
        } catch (\Exception $e) {
            $this->error('로그 파일 정리 실패: ' . $e->getMessage());
        }
    }

    /**
     * 파일 압축
     */
    private function compressFile($source, $destination)
    {
        $fp_out = gzopen($destination, 'wb9');
        $fp_in = fopen($source, 'rb');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
    }

    /**
     * 오래된 압축 로그 파일 삭제
     */
    private function cleanupOldCompressedLogs($logPath)
    {
        $files = glob($logPath . '/laravel.log.*.gz');
        $cutoffTime = now()->subDays(7)->timestamp;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info("오래된 로그 파일 삭제: {$file}");
            }
        }
    }

    /**
     * 데이터베이스 최적화
     */
    private function optimizeDatabase()
    {
        $this->info('데이터베이스 최적화 중...');
        
        try {
            // 세션 테이블 최적화
            DB::statement('OPTIMIZE TABLE sessions');
            
            // 인덱스 최적화
            $this->optimizeIndexes();
            
            $this->info('데이터베이스 최적화 완료');
            
        } catch (\Exception $e) {
            $this->error('데이터베이스 최적화 실패: ' . $e->getMessage());
        }
    }

    /**
     * 인덱스 최적화
     */
    private function optimizeIndexes()
    {
        try {
            // 세션 테이블 인덱스 최적화
            $sessionsTable = DB::table('sessions');
            
            // last_activity 인덱스 확인
            $indexes = DB::select("SHOW INDEX FROM sessions WHERE Key_name = 'sessions_last_activity_index'");
            
            if (empty($indexes)) {
                DB::statement('CREATE INDEX sessions_last_activity_index ON sessions (last_activity)');
                $this->info('세션 테이블 인덱스 생성 완료');
            }
            
        } catch (\Exception $e) {
            $this->error('인덱스 최적화 실패: ' . $e->getMessage());
        }
    }
}
