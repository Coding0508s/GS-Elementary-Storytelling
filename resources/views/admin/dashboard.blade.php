@extends('admin.layout')

@section('title', '대시보드')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-speedometer2"></i> 관리자 대시보드</h1>
    <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
</div>

<!-- 통계 카드 -->
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
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($pendingSubmissions) }}</h3>
                <p class="card-text text-muted">심사 대기</p>
            </div>
        </div>
    </div>
</div>

<!-- 진행률 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> 심사 진행률</h5>
    </div>
    <div class="card-body">
        @php
            $progressPercentage = $totalSubmissions > 0 ? round(($evaluatedSubmissions / $totalSubmissions) * 100, 1) : 0;
        @endphp
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>전체 진행률</span>
            <span class="fw-bold">{{ $progressPercentage }}%</span>
        </div>
        
        <div class="progress" style="height: 25px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 style="width: {{ $progressPercentage }}%"
                 aria-valuenow="{{ $progressPercentage }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                {{ $progressPercentage }}%
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-6 text-center">
                <small class="text-muted">심사 완료</small><br>
                <strong class="text-success">{{ $evaluatedSubmissions }}개</strong>
            </div>
            <div class="col-6 text-center">
                <small class="text-muted">심사 대기</small><br>
                <strong class="text-warning">{{ $pendingSubmissions }}개</strong>
            </div>
        </div>
    </div>
</div>

<!-- 빠른 작업 -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> 빠른 작업</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.evaluation.list', ['status' => 'pending']) }}" 
                       class="btn btn-admin">
                        <i class="bi bi-clipboard-check"></i> 심사 대기 목록 보기
                    </a>
                    
                    <a href="{{ route('admin.evaluation.list') }}" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-list-check"></i> 전체 제출 목록
                    </a>
                    
                    <a href="{{ route('admin.download.excel') }}" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i> 데이터 다운로드
                    </a>
                    
                    <a href="{{ route('admin.statistics') }}" 
                       class="btn btn-outline-info">
                        <i class="bi bi-graph-up"></i> 상세 통계 보기
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> 시스템 정보</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-server text-primary"></i>
                        <strong>시스템:</strong> Laravel {{ app()->version() }}
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-database text-info"></i>
                        <strong>데이터베이스:</strong> Supabase
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-calendar text-success"></i>
                        <strong>대회 기간:</strong> 진행 중
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-shield-check text-warning"></i>
                        <strong>보안:</strong> 활성화
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 최근 제출된 영상 -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 최근 제출된 영상</h5>
        <a href="{{ route('admin.evaluation.list') }}" class="btn btn-sm btn-outline-light">
            전체 보기 <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body">
        @if($recentSubmissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>제출일</th>
                            <th>학생명</th>
                            <th>기관</th>
                            <th>파일</th>
                            <th>상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSubmissions as $submission)
                        <tr>
                            <td>
                                <small>{{ $submission->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $submission->institution_name }}<br>
                                <small class="text-muted">{{ $submission->class_name }}</small>
                            </td>
                            <td>
                                <i class="bi bi-camera-video text-primary"></i>
                                {{ Str::limit($submission->video_file_name, 20) }}<br>
                                <small class="text-muted">{{ $submission->getFormattedFileSizeAttribute() }}</small>
                            </td>
                            <td>
                                @if($submission->evaluation)
                                    <span class="badge badge-evaluated">
                                        <i class="bi bi-check-circle"></i> 심사완료
                                    </span>
                                @else
                                    <span class="badge badge-pending">
                                        <i class="bi bi-clock"></i> 대기중
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.evaluation.show', $submission->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    @if($submission->evaluation)
                                        <i class="bi bi-eye"></i> 보기
                                    @else
                                        <i class="bi bi-clipboard-check"></i> 심사
                                    @endif
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2">제출된 영상이 없습니다.</p>
                <a href="{{ url('/') }}" class="btn btn-outline-primary" target="_blank">
                    <i class="bi bi-plus-circle"></i> 대회 페이지로 이동
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 자동 새로고침 (5분마다)
    setTimeout(function() {
        location.reload();
    }, 300000); // 5분
});
</script>
@endsection