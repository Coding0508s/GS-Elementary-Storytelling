@extends('layouts.app')

@section('title', '실시간 모니터링 대시보드')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-graph-up"></i> 실시간 모니터링 대시보드</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> 새로고침
                    </button>
                    <button class="btn btn-outline-success" onclick="exportReport()">
                        <i class="bi bi-file-earmark-excel"></i> 엑셀 리포트 다운로드
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 영역 -->
    <div class="row mb-4" id="alerts-container">
        <!-- 알림이 여기에 동적으로 표시됩니다 -->
    </div>

    <!-- 주요 지표 카드 -->
    <div class="row mb-4">
        <!-- 서버 상태 -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-server text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">서버 상태</h6>
                            <h4 class="mb-0" id="server-status">로딩중...</h4>
                            <small class="text-muted" id="server-details">CPU: --% | 메모리: --%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 동시 접속자 -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-people text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">동시 접속자</h6>
                            <h4 class="mb-0" id="concurrent-users">--</h4>
                            <small class="text-muted" id="user-details">세션: -- | 시간당: --</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 오류율 -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">오류율</h6>
                            <h4 class="mb-0" id="error-rate">--%</h4>
                            <small class="text-muted" id="error-details">응답시간: --ms</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 업로드 성공률 -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-cloud-upload text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">업로드 성공률</h6>
                            <h4 class="mb-0" id="upload-success">--%</h4>
                            <small class="text-muted" id="upload-details">총 업로드: --</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 차트 영역 -->
    <div class="row mb-4">
        <!-- 서버 리소스 차트 -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-cpu"></i> 서버 리소스 사용률</h5>
                </div>
                <div class="card-body">
                    <canvas id="server-resources-chart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- 동시 접속자 추이 -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> 동시 접속자 추이</h5>
                </div>
                <div class="card-body">
                    <canvas id="concurrent-users-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 오류율 및 성능 차트 -->
    <div class="row mb-4">
        <!-- 오류율 추이 -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> 오류율 추이</h5>
                </div>
                <div class="card-body">
                    <canvas id="error-rate-chart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- 응답 시간 -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-speedometer2"></i> 응답 시간</h5>
                </div>
                <div class="card-body">
                    <canvas id="response-time-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 상세 통계 테이블 -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-table"></i> 상세 통계</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>지표</th>
                                    <th>현재 값</th>
                                    <th>최소값</th>
                                    <th>최대값</th>
                                    <th>평균값</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody id="detailed-stats">
                                <!-- 상세 통계가 여기에 동적으로 표시됩니다 -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 전역 변수
let serverResourcesChart;
let concurrentUsersChart;
let errorRateChart;
let responseTimeChart;

// 차트 데이터 저장
let chartData = {
    serverResources: {
        labels: [],
        cpu: [],
        memory: [],
        disk: []
    },
    concurrentUsers: {
        labels: [],
        users: []
    },
    errorRate: {
        labels: [],
        rate: []
    },
    responseTime: {
        labels: [],
        time: []
    }
};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    startMonitoring();
    
    // 5초마다 데이터 새로고침
    setInterval(refreshDashboard, 5000);
});

// 차트 초기화
function initializeCharts() {
    // 서버 리소스 차트
    const serverCtx = document.getElementById('server-resources-chart').getContext('2d');
    serverResourcesChart = new Chart(serverCtx, {
        type: 'line',
        data: {
            labels: chartData.serverResources.labels,
            datasets: [
                {
                    label: 'CPU 사용률 (%)',
                    data: chartData.serverResources.cpu,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1
                },
                {
                    label: '메모리 사용률 (%)',
                    data: chartData.serverResources.memory,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1
                },
                {
                    label: '디스크 사용률 (%)',
                    data: chartData.serverResources.disk,
                    borderColor: 'rgb(255, 205, 86)',
                    backgroundColor: 'rgba(255, 205, 86, 0.1)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // 동시 접속자 차트
    const usersCtx = document.getElementById('concurrent-users-chart').getContext('2d');
    concurrentUsersChart = new Chart(usersCtx, {
        type: 'line',
        data: {
            labels: chartData.concurrentUsers.labels,
            datasets: [{
                label: '동시 접속자 수',
                data: chartData.concurrentUsers.users,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 오류율 차트
    const errorCtx = document.getElementById('error-rate-chart').getContext('2d');
    errorRateChart = new Chart(errorCtx, {
        type: 'line',
        data: {
            labels: chartData.errorRate.labels,
            datasets: [{
                label: '오류율 (%)',
                data: chartData.errorRate.rate,
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // 응답 시간 차트
    const responseCtx = document.getElementById('response-time-chart').getContext('2d');
    responseTimeChart = new Chart(responseCtx, {
        type: 'line',
        data: {
            labels: chartData.responseTime.labels,
            datasets: [{
                label: '응답 시간 (ms)',
                data: chartData.responseTime.time,
                borderColor: 'rgb(153, 102, 255)',
                backgroundColor: 'rgba(153, 102, 255, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// 모니터링 시작
function startMonitoring() {
    refreshDashboard();
}

// 대시보드 새로고침
async function refreshDashboard() {
    try {
        // 서버 상태 조회
        const serverStatus = await fetch('/admin/monitoring/server-status');
        const serverData = await serverStatus.json();
        
        if (serverData.success) {
            updateServerStatus(serverData.data);
        }

        // 동시 접속자 조회
        const concurrentUsers = await fetch('/admin/monitoring/concurrent-users');
        const usersData = await concurrentUsers.json();
        
        if (usersData.success) {
            updateConcurrentUsers(usersData.data);
        }

        // 오류율 조회
        const errorMetrics = await fetch('/admin/monitoring/error-metrics');
        const errorData = await errorMetrics.json();
        
        if (errorData.success) {
            updateErrorMetrics(errorData.data);
        }

        // 알림 조회
        const alerts = await fetch('/admin/monitoring/alerts');
        const alertsData = await alerts.json();
        
        if (alertsData.success) {
            updateAlerts(alertsData.alerts);
        }

    } catch (error) {
        console.error('모니터링 데이터 조회 실패:', error);
    }
}

// 서버 상태 업데이트
function updateServerStatus(data) {
    // 카드 업데이트
    document.getElementById('server-status').textContent = 
        data.cpu.usage > 80 ? '위험' : data.cpu.usage > 60 ? '주의' : '정상';
    
    document.getElementById('server-details').textContent = 
        `CPU: ${data.cpu.usage}% | 메모리: ${data.memory.usage}%`;

    // 차트 데이터 업데이트
    const now = new Date().toLocaleTimeString();
    chartData.serverResources.labels.push(now);
    chartData.serverResources.cpu.push(data.cpu.usage);
    chartData.serverResources.memory.push(data.memory.usage);
    chartData.serverResources.disk.push(data.disk.usage);

    // 최대 20개 데이터 포인트 유지
    if (chartData.serverResources.labels.length > 20) {
        chartData.serverResources.labels.shift();
        chartData.serverResources.cpu.shift();
        chartData.serverResources.memory.shift();
        chartData.serverResources.disk.shift();
    }

    serverResourcesChart.update();
}

// 동시 접속자 업데이트
function updateConcurrentUsers(data) {
    // 카드 업데이트
    document.getElementById('concurrent-users').textContent = data.current;
    document.getElementById('user-details').textContent = 
        `세션: ${data.current} | 시간당: ${data.hourly}`;

    // 차트 데이터 업데이트
    const now = new Date().toLocaleTimeString();
    chartData.concurrentUsers.labels.push(now);
    chartData.concurrentUsers.users.push(data.current);

    // 최대 20개 데이터 포인트 유지
    if (chartData.concurrentUsers.labels.length > 20) {
        chartData.concurrentUsers.labels.shift();
        chartData.concurrentUsers.users.shift();
    }

    concurrentUsersChart.update();
}

// 오류율 업데이트
function updateErrorMetrics(data) {
    // 카드 업데이트
    document.getElementById('error-rate').textContent = `${data.error_rate}%`;
    document.getElementById('error-details').textContent = 
        `응답시간: ${data.response_time.avg}ms`;

    // 업로드 성공률 업데이트
    document.getElementById('upload-success').textContent = `${data.upload_success.rate}%`;
    document.getElementById('upload-details').textContent = 
        `총 업로드: ${data.upload_success.total}`;

    // 차트 데이터 업데이트
    const now = new Date().toLocaleTimeString();
    chartData.errorRate.labels.push(now);
    chartData.errorRate.rate.push(data.error_rate);
    
    chartData.responseTime.labels.push(now);
    chartData.responseTime.time.push(data.response_time.avg);

    // 최대 20개 데이터 포인트 유지
    if (chartData.errorRate.labels.length > 20) {
        chartData.errorRate.labels.shift();
        chartData.errorRate.rate.shift();
    }
    
    if (chartData.responseTime.labels.length > 20) {
        chartData.responseTime.labels.shift();
        chartData.responseTime.time.shift();
    }

    errorRateChart.update();
    responseTimeChart.update();
}

// 알림 업데이트
function updateAlerts(alerts) {
    const container = document.getElementById('alerts-container');
    
    if (alerts.length === 0) {
        container.innerHTML = '';
        return;
    }

    let alertsHtml = '';
    alerts.forEach(alert => {
        const alertClass = alert.type === 'critical' ? 'alert-danger' : 'alert-warning';
        alertsHtml += `
            <div class="col-12">
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${alert.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        `;
    });

    container.innerHTML = alertsHtml;
}

// 리포트 다운로드 (엑셀 파일)
function exportReport() {
    // 서버에서 엑셀 파일 생성 요청
    fetch('/admin/monitoring/export-excel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            serverResources: chartData.serverResources,
            concurrentUsers: chartData.concurrentUsers,
            errorRate: chartData.errorRate,
            responseTime: chartData.responseTime
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('엑셀 파일 생성에 실패했습니다.');
        }
        return response.blob();
    })
    .then(blob => {
        // 엑셀 파일 다운로드
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `monitoring-report-${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('리포트 다운로드 오류:', error);
        alert('리포트 다운로드 중 오류가 발생했습니다: ' + error.message);
    });
}
</script>
@endsection
