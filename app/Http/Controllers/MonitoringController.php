<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitoringController extends Controller
{
    /**
     * 실시간 모니터링 대시보드
     */
    public function dashboard()
    {
        return view('admin.monitoring.dashboard');
    }

    /**
     * 서버 리소스 상태 조회
     */
    public function getServerStatus()
    {
        try {
            // CPU 사용률
            $cpuUsage = $this->getCpuUsage();
            
            // 메모리 사용률
            $memoryUsage = $this->getMemoryUsage();
            
            // 디스크 사용률
            $diskUsage = $this->getDiskUsage();
            
            // 활성 연결 수
            $activeConnections = $this->getActiveConnections();
            
            // PHP-FPM 프로세스 상태
            $phpFpmStatus = $this->getPhpFpmStatus();
            
            // 데이터베이스 연결 상태
            $databaseStatus = $this->getDatabaseStatus();
            
            // S3 연결 상태
            $s3Status = $this->getS3Status();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cpu' => $cpuUsage,
                    'memory' => $memoryUsage,
                    'disk' => $diskUsage,
                    'connections' => $activeConnections,
                    'php_fpm' => $phpFpmStatus,
                    'database' => $databaseStatus,
                    's3' => $s3Status,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('서버 상태 조회 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '서버 상태 조회에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 동시 접속자 통계
     */
    public function getConcurrentUsers()
    {
        try {
            // 현재 활성 세션 수
            $activeSessions = $this->getActiveSessions();
            
            // 최근 1시간 접속자 수
            $hourlyUsers = $this->getHourlyUsers();
            
            // 최근 24시간 접속자 수
            $dailyUsers = $this->getDailyUsers();
            
            // 피크 시간대 분석
            $peakHours = $this->getPeakHours();
            
            // 지역별 접속자 분포
            $regionalDistribution = $this->getRegionalDistribution();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current' => $activeSessions,
                    'hourly' => $hourlyUsers,
                    'daily' => $dailyUsers,
                    'peak_hours' => $peakHours,
                    'regional' => $regionalDistribution,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('동시 접속자 통계 조회 실패', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '동시 접속자 통계 조회에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 오류율 및 성능 지표
     */
    public function getErrorMetrics()
    {
        try {
            // 최근 1시간 오류율
            $hourlyErrorRate = $this->getHourlyErrorRate();
            
            // 오류 유형별 분포
            $errorTypes = $this->getErrorTypes();
            
            // 응답 시간 통계
            $responseTime = $this->getResponseTimeStats();
            
            // Rate Limiting 통계
            $rateLimitStats = $this->getRateLimitStats();
            
            // S3 업로드 성공률
            $uploadSuccessRate = $this->getUploadSuccessRate();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'error_rate' => $hourlyErrorRate,
                    'error_types' => $errorTypes,
                    'response_time' => $responseTime,
                    'rate_limits' => $rateLimitStats,
                    'upload_success' => $uploadSuccessRate,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('오류율 지표 조회 실패', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '오류율 지표 조회에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 실시간 알림 설정
     */
    public function getAlerts()
    {
        try {
            $alerts = [];
            
            // CPU 사용률 알림
            $cpuUsage = $this->getCpuUsage();
            if ($cpuUsage['usage'] > 80) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "CPU 사용률이 높습니다: {$cpuUsage['usage']}%",
                    'timestamp' => now()->toISOString()
                ];
            }
            
            // 메모리 사용률 알림
            $memoryUsage = $this->getMemoryUsage();
            if ($memoryUsage['usage'] > 85) {
                $alerts[] = [
                    'type' => 'critical',
                    'message' => "메모리 사용률이 위험 수준입니다: {$memoryUsage['usage']}%",
                    'timestamp' => now()->toISOString()
                ];
            }
            
            // 오류율 알림
            $errorRate = $this->getHourlyErrorRate();
            if ($errorRate > 10) {
                $alerts[] = [
                    'type' => 'critical',
                    'message' => "오류율이 높습니다: {$errorRate}%",
                    'timestamp' => now()->toISOString()
                ];
            }
            
            // 동시 접속자 알림
            $activeSessions = $this->getActiveSessions();
            if ($activeSessions > 200) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "동시 접속자가 많습니다: {$activeSessions}명",
                    'timestamp' => now()->toISOString()
                ];
            }
            
            return response()->json([
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts)
            ]);
            
        } catch (\Exception $e) {
            Log::error('알림 조회 실패', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '알림 조회에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 엑셀 리포트 다운로드
     */
    public function exportExcel(Request $request)
    {
        try {
            // 데이터 검증 (선택적)
            $data = $request->all();
            
            // 기본 데이터 구조 설정
            $defaultData = [
                'serverResources' => [
                    'labels' => [],
                    'cpu' => [],
                    'memory' => [],
                    'disk' => []
                ],
                'concurrentUsers' => [
                    'labels' => [],
                    'users' => []
                ],
                'errorRate' => [
                    'labels' => [],
                    'rate' => []
                ],
                'responseTime' => [
                    'labels' => [],
                    'time' => []
                ]
            ];
            
            // 요청 데이터와 기본 데이터 병합
            $data = array_merge($defaultData, $data);
            
            // PhpSpreadsheet를 사용하여 엑셀 파일 생성
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            
            // 기본 시트 제거
            $spreadsheet->removeSheetByIndex(0);
            
            // 서버 리소스 시트
            $this->createServerResourcesSheet($spreadsheet, $data['serverResources']);
            
            // 동시 접속자 시트
            $this->createConcurrentUsersSheet($spreadsheet, $data['concurrentUsers']);
            
            // 오류율 시트
            $this->createErrorRateSheet($spreadsheet, $data['errorRate']);
            
            // 응답 시간 시트
            $this->createResponseTimeSheet($spreadsheet, $data['responseTime']);
            
            // 요약 시트
            $this->createSummarySheet($spreadsheet, $data);
            
            // 엑셀 파일 생성
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // 임시 파일에 저장
            $tempFile = tempnam(sys_get_temp_dir(), 'monitoring_report_');
            $writer->save($tempFile);
            
            // 파일명 생성
            $filename = 'monitoring-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('엑셀 리포트 생성 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '엑셀 리포트 생성에 실패했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 서버 리소스 시트 생성
     */
    private function createServerResourcesSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('서버 리소스');
        
        // 헤더 설정
        $headers = ['시간', 'CPU 사용률 (%)', '메모리 사용률 (%)', '디스크 사용률 (%)'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 데이터 입력
        $row = 2;
        $labels = $data['labels'] ?? [];
        $cpu = $data['cpu'] ?? [];
        $memory = $data['memory'] ?? [];
        $disk = $data['disk'] ?? [];
        
        // 데이터가 없으면 현재 값으로 채움
        if (empty($labels)) {
            $currentCpu = $this->getCpuUsage();
            $currentMemory = $this->getMemoryUsage();
            $currentDisk = $this->getDiskUsage();
            
            $sheet->setCellValue('A2', now()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B2', $currentCpu['usage'] ?? 0);
            $sheet->setCellValue('C2', $currentMemory['usage'] ?? 0);
            $sheet->setCellValue('D2', $currentDisk['usage'] ?? 0);
        } else {
            for ($i = 0; $i < count($labels); $i++) {
                $sheet->setCellValue('A' . $row, $labels[$i] ?? '');
                $sheet->setCellValue('B' . $row, $cpu[$i] ?? 0);
                $sheet->setCellValue('C' . $row, $memory[$i] ?? 0);
                $sheet->setCellValue('D' . $row, $disk[$i] ?? 0);
                $row++;
            }
        }
        
        // 스타일 적용
        $this->applySheetStyles($sheet, 'A1:D1');
    }

    /**
     * 동시 접속자 시트 생성
     */
    private function createConcurrentUsersSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('동시 접속자');
        
        // 헤더 설정
        $headers = ['시간', '접속자 수'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 데이터 입력
        $row = 2;
        $labels = $data['labels'] ?? [];
        $users = $data['users'] ?? [];
        
        // 데이터가 없으면 현재 값으로 채움
        if (empty($labels)) {
            $currentUsers = $this->getActiveSessions();
            $sheet->setCellValue('A2', now()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B2', $currentUsers);
        } else {
            for ($i = 0; $i < count($labels); $i++) {
                $sheet->setCellValue('A' . $row, $labels[$i] ?? '');
                $sheet->setCellValue('B' . $row, $users[$i] ?? 0);
                $row++;
            }
        }
        
        // 스타일 적용
        $this->applySheetStyles($sheet, 'A1:B1');
    }

    /**
     * 오류율 시트 생성
     */
    private function createErrorRateSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('오류율');
        
        // 헤더 설정
        $headers = ['시간', '오류율 (%)'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 데이터 입력
        $row = 2;
        $labels = $data['labels'] ?? [];
        $rate = $data['rate'] ?? [];
        
        // 데이터가 없으면 현재 값으로 채움
        if (empty($labels)) {
            $currentErrorRate = $this->getHourlyErrorRate();
            $sheet->setCellValue('A2', now()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B2', $currentErrorRate);
        } else {
            for ($i = 0; $i < count($labels); $i++) {
                $sheet->setCellValue('A' . $row, $labels[$i] ?? '');
                $sheet->setCellValue('B' . $row, $rate[$i] ?? 0);
                $row++;
            }
        }
        
        // 스타일 적용
        $this->applySheetStyles($sheet, 'A1:B1');
    }

    /**
     * 응답 시간 시트 생성
     */
    private function createResponseTimeSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('응답 시간');
        
        // 헤더 설정
        $headers = ['시간', '응답 시간 (ms)'];
        $sheet->fromArray($headers, null, 'A1');
        
        // 데이터 입력
        $row = 2;
        $labels = $data['labels'] ?? [];
        $time = $data['time'] ?? [];
        
        // 데이터가 없으면 현재 값으로 채움
        if (empty($labels)) {
            $currentResponseTime = $this->getAverageResponseTime();
            $sheet->setCellValue('A2', now()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B2', $currentResponseTime);
        } else {
            for ($i = 0; $i < count($labels); $i++) {
                $sheet->setCellValue('A' . $row, $labels[$i] ?? '');
                $sheet->setCellValue('B' . $row, $time[$i] ?? 0);
                $row++;
            }
        }
        
        // 스타일 적용
        $this->applySheetStyles($sheet, 'A1:B1');
    }

    /**
     * 요약 시트 생성
     */
    private function createSummarySheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('요약');
        
        // 현재 서버 상태 조회 (직접 데이터 메서드 호출)
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        $diskUsage = $this->getDiskUsage();
        $activeSessions = $this->getActiveSessions();
        $hourlyUsers = $this->getHourlyUsers();
        $dailyUsers = $this->getDailyUsers();
        $errorRate = $this->getHourlyErrorRate();
        $responseTime = $this->getAverageResponseTime();
        $uploadSuccess = $this->getUploadSuccessRate();
        
        // 요약 데이터
        $summaryData = [
            ['항목', '현재 값', '단위'],
            ['리포트 생성 시간', now()->format('Y-m-d H:i:s'), ''],
            ['', '', ''],
            ['=== 서버 리소스 ===', '', ''],
            ['CPU 사용률', $cpuUsage['usage'] ?? 0, '%'],
            ['메모리 사용률', $memoryUsage['usage'] ?? 0, '%'],
            ['디스크 사용률', $diskUsage['usage'] ?? 0, '%'],
            ['', '', ''],
            ['=== 접속자 통계 ===', '', ''],
            ['현재 접속자', $activeSessions, '명'],
            ['시간당 접속자', $hourlyUsers, '명'],
            ['일일 접속자', $dailyUsers, '명'],
            ['', '', ''],
            ['=== 성능 지표 ===', '', ''],
            ['오류율', $errorRate, '%'],
            ['평균 응답 시간', $responseTime, 'ms'],
            ['업로드 성공률', $uploadSuccess, '%'],
        ];
        
        // 데이터 입력
        $sheet->fromArray($summaryData, null, 'A1');
        
        // 스타일 적용
        $this->applySheetStyles($sheet, 'A1:C1');
        
        // 열 너비 자동 조정
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * 시트 스타일 적용
     */
    private function applySheetStyles($sheet, $headerRange)
    {
        // 헤더 스타일
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // 테두리 적용
        $sheet->getStyle($headerRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]);
    }

    /**
     * 디스크 사용률 조회
     */
    private function getDiskUsage()
    {
        try {
            $totalSpace = disk_total_space('/');
            $freeSpace = disk_free_space('/');
            $usedSpace = $totalSpace - $freeSpace;
            $usage = round(($usedSpace / $totalSpace) * 100, 2);
            
            return [
                'usage' => $usage,
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace)
            ];
        } catch (\Exception $e) {
            return ['usage' => 0, 'total' => '0 B', 'used' => '0 B', 'free' => '0 B'];
        }
    }

    /**
     * 시간당 접속자 수 조회
     */
    private function getHourlyUsers()
    {
        try {
            return \DB::table('sessions')
                ->where('last_activity', '>=', now()->subHour()->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 일일 접속자 수 조회
     */
    private function getDailyUsers()
    {
        try {
            return \DB::table('sessions')
                ->where('last_activity', '>=', now()->subDay()->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 평균 응답 시간 조회
     */
    private function getAverageResponseTime()
    {
        try {
            $responseTime = \Cache::get('monitoring_response_time', []);
            if (empty($responseTime)) {
                return 0;
            }
            
            return round(array_sum($responseTime) / count($responseTime), 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 업로드 성공률 조회
     */
    private function getUploadSuccessRate()
    {
        try {
            $totalUploads = \DB::table('video_submissions')->count();
            $successfulUploads = \DB::table('video_submissions')
                ->where('status', 'completed')
                ->count();
            
            if ($totalUploads == 0) {
                return 0;
            }
            
            return round(($successfulUploads / $totalUploads) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 바이트를 읽기 쉬운 형태로 변환
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * CPU 사용률 조회
     */
    private function getCpuUsage()
    {
        $loadAvg = sys_getloadavg();
        $cpuCount = $this->getCpuCount();
        
        return [
            'load_1min' => $loadAvg[0] ?? 0,
            'load_5min' => $loadAvg[1] ?? 0,
            'load_15min' => $loadAvg[2] ?? 0,
            'usage' => round(($loadAvg[0] / $cpuCount) * 100, 2),
            'cpu_count' => $cpuCount
        ];
    }

    /**
     * 메모리 사용률 조회
     */
    private function getMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        return [
            'used' => $this->formatBytes($memoryUsage),
            'limit' => $memoryLimit,
            'usage' => round($memoryPercent, 2),
            'free' => $this->formatBytes($memoryLimitBytes - $memoryUsage)
        ];
    }

    /**
     * 디스크 사용률 조회
     */
    private function getDiskUsage()
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = ($diskUsed / $diskTotal) * 100;
        
        return [
            'total' => $this->formatBytes($diskTotal),
            'used' => $this->formatBytes($diskUsed),
            'free' => $this->formatBytes($diskFree),
            'usage' => round($diskPercent, 2)
        ];
    }

    /**
     * 활성 연결 수 조회
     */
    private function getActiveConnections()
    {
        $cacheKey = 'storytelling:active_connections';
        $activeConnections = Cache::get($cacheKey, 0);
        
        // 세션 테이블에서 활성 세션 수 확인
        try {
            $sessionCount = DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                ->count();
        } catch (\Exception $e) {
            $sessionCount = 0;
        }
        
        return [
            'cached' => $activeConnections,
            'sessions' => $sessionCount,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * PHP-FPM 상태 조회
     */
    private function getPhpFpmStatus()
    {
        try {
            // PHP-FPM 상태 파일에서 정보 읽기
            $statusFile = '/var/run/php/php8.2-fpm.sock';
            if (file_exists($statusFile)) {
                return [
                    'status' => 'running',
                    'socket' => $statusFile
                ];
            }
            
            return [
                'status' => 'unknown',
                'socket' => 'not_found'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 데이터베이스 연결 상태
     */
    private function getDatabaseStatus()
    {
        try {
            $connectionCount = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 0;
            
            return [
                'status' => 'connected',
                'connections' => $connectionCount,
                'max_connections' => $maxConnections,
                'usage_percent' => round(($connectionCount / $maxConnections) * 100, 2)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * S3 연결 상태
     */
    private function getS3Status()
    {
        try {
            $s3Client = app('aws')->createS3();
            $s3Client->headBucket(['Bucket' => config('filesystems.disks.s3.bucket')]);
            
            return [
                'status' => 'connected',
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 활성 세션 수 조회
     */
    private function getActiveSessions()
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 최근 1시간 접속자 수
     */
    private function getHourlyUsers()
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subHour()->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 최근 24시간 접속자 수
     */
    private function getDailyUsers()
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subDay()->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 피크 시간대 분석
     */
    private function getPeakHours()
    {
        try {
            $peakHours = DB::table('sessions')
                ->selectRaw('HOUR(FROM_UNIXTIME(last_activity)) as hour, COUNT(*) as count')
                ->where('last_activity', '>', now()->subDay()->timestamp)
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();
            
            return $peakHours->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 지역별 접속자 분포
     */
    private function getRegionalDistribution()
    {
        try {
            // IP 기반 지역 분석 (간단한 구현)
            $sessions = DB::table('sessions')
                ->where('last_activity', '>', now()->subHour()->timestamp)
                ->get();
            
            $regions = [];
            foreach ($sessions as $session) {
                $ip = $session->ip_address ?? 'unknown';
                $region = $this->getRegionFromIp($ip);
                $regions[$region] = ($regions[$region] ?? 0) + 1;
            }
            
            return $regions;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 최근 1시간 오류율
     */
    private function getHourlyErrorRate()
    {
        try {
            $totalRequests = Cache::get('storytelling:total_requests', 0);
            $errorRequests = Cache::get('storytelling:error_requests', 0);
            
            if ($totalRequests > 0) {
                return round(($errorRequests / $totalRequests) * 100, 2);
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 오류 유형별 분포
     */
    private function getErrorTypes()
    {
        try {
            $errorTypes = Cache::get('storytelling:error_types', []);
            return $errorTypes;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 응답 시간 통계
     */
    private function getResponseTimeStats()
    {
        try {
            $responseTimes = Cache::get('storytelling:response_times', []);
            
            if (empty($responseTimes)) {
                return [
                    'avg' => 0,
                    'min' => 0,
                    'max' => 0,
                    'p95' => 0
                ];
            }
            
            sort($responseTimes);
            $count = count($responseTimes);
            
            return [
                'avg' => round(array_sum($responseTimes) / $count, 2),
                'min' => min($responseTimes),
                'max' => max($responseTimes),
                'p95' => $responseTimes[floor($count * 0.95)]
            ];
        } catch (\Exception $e) {
            return [
                'avg' => 0,
                'min' => 0,
                'max' => 0,
                'p95' => 0
            ];
        }
    }

    /**
     * Rate Limiting 통계
     */
    private function getRateLimitStats()
    {
        try {
            $rateLimitHits = Cache::get('storytelling:rate_limit_hits', 0);
            $totalRequests = Cache::get('storytelling:total_requests', 0);
            
            return [
                'hits' => $rateLimitHits,
                'total' => $totalRequests,
                'rate' => $totalRequests > 0 ? round(($rateLimitHits / $totalRequests) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            return [
                'hits' => 0,
                'total' => 0,
                'rate' => 0
            ];
        }
    }

    /**
     * S3 업로드 성공률
     */
    private function getUploadSuccessRate()
    {
        try {
            $totalUploads = Cache::get('storytelling:total_uploads', 0);
            $successfulUploads = Cache::get('storytelling:successful_uploads', 0);
            
            return [
                'total' => $totalUploads,
                'successful' => $successfulUploads,
                'rate' => $totalUploads > 0 ? round(($successfulUploads / $totalUploads) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'successful' => 0,
                'rate' => 0
            ];
        }
    }

    /**
     * CPU 코어 수 조회
     */
    private function getCpuCount()
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        }
        
        return 1; // 기본값
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

    /**
     * 바이트를 읽기 쉬운 형태로 변환
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * IP에서 지역 추출 (간단한 구현)
     */
    private function getRegionFromIp($ip)
    {
        // 실제로는 GeoIP 라이브러리 사용 권장
        if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return 'Local';
        }
        
        return 'Unknown';
    }
}
