@extends('admin.layout')

@section('title', '통계 현황')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-graph-up"></i> 통계 현황</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.download.excel') }}" 
           class="btn btn-success">
            <i class="bi bi-download"></i> 데이터 다운로드
        </a>
        <a href="{{ route('admin.dashboard') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드
        </a>
    </div>
</div>

<!-- 전체 현황 -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalSubmissions) }}</h3>
                <p class="card-text text-muted">총 제출 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($evaluatedSubmissions) }}</h3>
                <p class="card-text text-muted">심사 완료</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-percent"></i>
                </div>
                <h3 class="text-info">
                    {{ $totalSubmissions > 0 ? number_format(($evaluatedSubmissions / $totalSubmissions) * 100, 1) : 0 }}%
                </h3>
                <p class="card-text text-muted">심사 진행률</p>
            </div>
        </div>
    </div>
</div>

@if($evaluatedSubmissions > 0)
<!-- 평균 점수 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> 평균 점수 현황</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 mb-3">
                <div class="text-center">
                    <h4 class="text-primary">{{ number_format($averageScores->avg_pronunciation ?? 0, 1) }}</h4>
                    <small class="text-muted">발음·억양</small>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="text-center">
                    <h4 class="text-success">{{ number_format($averageScores->avg_vocabulary ?? 0, 1) }}</h4>
                    <small class="text-muted">어휘·표현</small>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="text-center">
                    <h4 class="text-info">{{ number_format($averageScores->avg_fluency ?? 0, 1) }}</h4>
                    <small class="text-muted">유창성</small>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="text-center">
                    <h4 class="text-warning">{{ number_format($averageScores->avg_confidence ?? 0, 1) }}</h4>
                    <small class="text-muted">자신감</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="text-center">
                    <h3 class="text-danger">{{ number_format($averageScores->avg_total ?? 0, 1) }}/40</h3>
                    <small class="text-muted">전체 평균</small>
                </div>
            </div>
        </div>
        
        <!-- 평균 점수 차트 영역 -->
        <div class="mt-4">
            <canvas id="averageChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- 점수 분포 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> 점수 분포</h5>
    </div>
    <div class="card-body">
        @if($scoreDistribution->count() > 0)
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                @foreach($scoreDistribution as $dist)
                <div class="text-center mx-2 mb-2">
                    <span class="badge fs-6 px-2 py-1
                        @if(Str::contains($dist->grade, '우수')) bg-success
                        @elseif(Str::contains($dist->grade, '양호')) bg-primary
                        @elseif(Str::contains($dist->grade, '보통')) bg-info
                        @elseif(Str::contains($dist->grade, '미흡')) bg-warning
                        @else bg-danger
                        @endif
                    ">
                        {{ $dist->grade }} {{ $dist->count }}명
                    </span>
                    <div>
                        <small class="text-muted" style="font-size: 0.7rem;">
                            {{ number_format(($dist->count / $evaluatedSubmissions) * 100, 1) }}%
                        </small>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-pie-chart display-4 text-muted"></i>
                <p class="text-muted mt-2">심사 완료된 영상이 없습니다.</p>
            </div>
        @endif
    </div>
</div>

<!-- 학생 순위 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-trophy"></i> 학생 순위 (상위 20명)</h5>
    </div>
    <div class="card-body">
        @if($studentRankings->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th style="width: 80px;">순위</th>
                            <th>학생명</th>
                            <th>학급</th>
                            <th>기관명</th>
                            <th>발음·억양</th>
                            <th>어휘·표현</th>
                            <th>유창성</th>
                            <th>자신감</th>
                            <th>총점</th>
                            <th>등급</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentRankings as $student)
                        <tr>
                            <td>
                                @if($student->rank <= 3)
                                    <span class="badge fs-6
                                        @if($student->rank === 1) bg-warning text-dark
                                        @elseif($student->rank === 2) bg-secondary
                                        @else bg-info
                                        @endif">
                                        @if($student->rank === 1) 🥇
                                        @elseif($student->rank === 2) 🥈
                                        @else 🥉
                                        @endif
                                        {{ $student->rank }}위
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $student->rank }}위</span>
                                @endif
                            </td>
                            <td><strong>{{ $student->student_name }}</strong></td>
                            <td>{{ $student->grade_class }}</td>
                            <td>{{ $student->institution_name }}</td>
                            <td><span class="badge bg-primary">{{ $student->pronunciation_score }}</span></td>
                            <td><span class="badge bg-success">{{ $student->vocabulary_score }}</span></td>
                            <td><span class="badge bg-info">{{ $student->fluency_score }}</span></td>
                            <td><span class="badge bg-warning">{{ $student->confidence_score }}</span></td>
                            <td><strong class="text-danger">{{ $student->total_score }}/40</strong></td>
                            <td>
                                @php
                                    $grade = '';
                                    $class = '';
                                    if ($student->total_score >= 36) {
                                        $grade = '우수';
                                        $class = 'bg-success';
                                    } elseif ($student->total_score >= 31) {
                                        $grade = '양호';
                                        $class = 'bg-primary';
                                    } elseif ($student->total_score >= 26) {
                                        $grade = '보통';
                                        $class = 'bg-info';
                                    } elseif ($student->total_score >= 21) {
                                        $grade = '미흡';
                                        $class = 'bg-warning';
                                    } else {
                                        $grade = '매우 미흡';
                                        $class = 'bg-danger';
                                    }
                                @endphp
                                <span class="badge {{ $class }}">{{ $grade }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-trophy display-4 text-muted"></i>
                <p class="text-muted mt-2">심사 완료된 학생이 없습니다.</p>
            </div>
        @endif
    </div>
</div>

<!-- 기관별 통계 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-building"></i> 기관별 통계</h5>
    </div>
    <div class="card-body">
        @if($institutionStats->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>순위</th>
                            <th>기관명</th>
                            <th>제출 수</th>
                            <th>평균 점수</th>
                            <th>등급</th>
                            <th>진행률</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($institutionStats as $index => $stat)
                        <tr>
                            <td>
                                @if($index < 3)
                                    <span class="badge 
                                        @if($index === 0) bg-warning
                                        @elseif($index === 1) bg-secondary
                                        @else bg-info
                                        @endif">
                                        {{ $index + 1 }}위
                                    </span>
                                @else
                                    {{ $index + 1 }}위
                                @endif
                            </td>
                            <td>
                                <strong>{{ $stat->institution_name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $stat->submission_count }}개</span>
                            </td>
                            <td>
                                <strong class="text-success">{{ number_format($stat->avg_score, 1) }}/40</strong>
                            </td>
                            <td>
                                @php
                                    $grade = '';
                                    $class = '';
                                    if ($stat->avg_score >= 36) {
                                        $grade = '우수';
                                        $class = 'bg-success';
                                    } elseif ($stat->avg_score >= 31) {
                                        $grade = '양호';
                                        $class = 'bg-primary';
                                    } elseif ($stat->avg_score >= 26) {
                                        $grade = '보통';
                                        $class = 'bg-info';
                                    } elseif ($stat->avg_score >= 21) {
                                        $grade = '미흡';
                                        $class = 'bg-warning';
                                    } else {
                                        $grade = '매우 미흡';
                                        $class = 'bg-danger';
                                    }
                                @endphp
                                <span class="badge {{ $class }}">{{ $grade }}</span>
                            </td>
                            <td>
                                @php
                                    $totalForInstitution = \App\Models\VideoSubmission::where('institution_name', $stat->institution_name)->count();
                                    $progressPercent = $totalForInstitution > 0 ? ($stat->submission_count / $totalForInstitution) * 100 : 0;
                                @endphp
                                <div class="progress" style="height: 20px; width: 100px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: {{ $progressPercent }}%">
                                        {{ number_format($progressPercent, 0) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-building display-4 text-muted"></i>
                <p class="text-muted mt-2">기관별 통계 데이터가 없습니다.</p>
            </div>
        @endif
    </div>
</div>

@else
<!-- 심사 완료된 영상이 없을 때 -->
<div class="card admin-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-graph-up display-1 text-muted"></i>
        <h3 class="text-muted mt-3">통계 데이터가 없습니다</h3>
        <p class="text-muted">심사가 완료된 영상이 있어야 통계를 확인할 수 있습니다.</p>
        <a href="{{ route('admin.evaluation.list', ['status' => 'pending']) }}" 
           class="btn btn-admin">
            <i class="bi bi-clipboard-check"></i> 심사 대기 목록 보기
        </a>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if($evaluatedSubmissions > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 평균 점수 차트
    const avgCtx = document.getElementById('averageChart').getContext('2d');
    new Chart(avgCtx, {
        type: 'bar',
        data: {
            labels: ['발음·억양', '어휘·표현', '유창성', '자신감'],
            datasets: [{
                label: '평균 점수',
                data: [
                    {{ $averageScores->avg_pronunciation ?? 0 }},
                    {{ $averageScores->avg_vocabulary ?? 0 }},
                    {{ $averageScores->avg_fluency ?? 0 }},
                    {{ $averageScores->avg_confidence ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    @if($scoreDistribution->count() > 0)
    // 점수 분포 차트
    const distCtx = document.getElementById('distributionChart').getContext('2d');
    new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($scoreDistribution->pluck('grade')->toArray()) !!},
            datasets: [{
                data: {!! json_encode($scoreDistribution->pluck('count')->toArray()) !!},
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',   // 우수 - 초록
                    'rgba(0, 123, 255, 0.8)',   // 양호 - 파랑
                    'rgba(23, 162, 184, 0.8)',  // 보통 - 청록
                    'rgba(255, 193, 7, 0.8)',   // 미흡 - 노랑
                    'rgba(220, 53, 69, 0.8)'    // 매우 미흡 - 빨강
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    @endif
});
</script>
@endif
@endsection