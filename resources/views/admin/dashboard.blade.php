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
    
    <div class="col-md-3 mb-3">
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
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-person-check"></i>
                </div>
                <h3 class="text-info">{{ number_format($assignedSubmissions) }}</h3>
                <p class="card-text text-muted">배정된 영상</p>
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
                    <a href="{{ route('admin.assignment.list') }}" 
                       class="btn btn-admin">
                        <i class="bi bi-person-check"></i> 영상 배정 관리
                    </a>
                    <a href="{{ route('admin.evaluation.list', ['status' => 'pending']) }}" 
                       class="btn btn-admin">
                        <i class="bi bi-clipboard-check"></i> 심사 대기 목록 보기
                    </a>
                    <a href="{{ route('judge.dashboard') }}" 
                       class="btn btn-admin">
                        <i class="bi bi-person-badge"></i> 심사위원 페이지로 이동
                    </a>
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

<!-- 관리자 전용 위험 구역 -->
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle"></i> 
            위험 구역 (관리자 전용)
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>⚠️ 주의:</strong> 아래 기능들은 되돌릴 수 없는 작업입니다.
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <h6 class="text-danger"><i class="bi bi-trash"></i> 모든 데이터 초기화</h6>
                <p class="text-muted mb-2">
                    모든 영상, 심사, 배정 데이터를 영구적으로 삭제합니다.
                    <br><small>※ 관리자 계정은 유지됩니다.</small>
                </p>
                <ul class="text-muted small">
                    <li>영상 제출 데이터: {{ number_format($totalSubmissions) }}개</li>
                    <li>심사 결과: {{ number_format($evaluatedSubmissions) }}개</li>
                    <li>S3 저장 파일 포함</li>
                </ul>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" 
                        class="btn btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#resetWarningModal">
                    <i class="bi bi-exclamation-triangle"></i> 데이터 초기화
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 데이터 초기화 경고 모달 -->
<div class="modal fade" id="resetWarningModal" tabindex="-1" aria-labelledby="resetWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="resetWarningModalLabel">
                    <i class="bi bi-exclamation-triangle"></i> 데이터 초기화 경고
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6 class="text-danger"><strong>⚠️ 위험한 작업입니다!</strong></h6>
                    <p class="mb-2">이 작업을 수행하면 다음 데이터가 <strong>영구적으로 삭제</strong>됩니다:</p>
                    <ul class="mb-0">
                        <li><strong>모든 영상 제출 데이터</strong> ({{ number_format($totalSubmissions) }}개)</li>
                        <li><strong>모든 심사 결과</strong> ({{ number_format($evaluatedSubmissions) }}개)</li>
                        <li><strong>모든 배정 정보</strong></li>
                        <li><strong>S3에 저장된 모든 영상 파일</strong></li>
                        <li><strong>관련된 모든 로그</strong></li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <h6 class="text-info"><strong>💡 유지되는 데이터:</strong></h6>
                    <ul class="mb-0">
                        <li>관리자 계정 정보</li>
                        <li>심사위원 계정 정보</li>
                        <li>시스템 설정</li>
                    </ul>
                </div>
                
                <div class="mt-3">
                    <p class="text-muted">
                        <strong>주의사항:</strong>
                        <br>• 이 작업은 <strong class="text-danger">되돌릴 수 없습니다</strong>
                        <br>• 작업 전에 필요한 데이터를 백업해두세요
                        <br>• 보안을 위해 추가 확인 절차가 있습니다
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> 취소
                </button>
                <a href="{{ route('admin.reset.confirmation') }}" class="btn btn-danger">
                    <i class="bi bi-arrow-right"></i> 계속 진행
                </a>
            </div>
        </div>
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