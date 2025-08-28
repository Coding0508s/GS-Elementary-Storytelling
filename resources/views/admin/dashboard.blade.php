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
                <p class="card-text text-muted">총 접수 영상</p>
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
                        <i class="bi bi-list-check"></i> 전체 접수 목록
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
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 최근 접수된 영상</h5>
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
                            <th>접수번호</th>
                            <th>접수일</th>
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
                                <small>{{ $submission->receipt_number }}</small>
                            </td>
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
                <p class="text-muted mt-2">접수된 영상이 없습니다.</p>
                <a href="{{ url('/') }}" class="btn btn-outline-primary" target="_blank">
                    <i class="bi bi-plus-circle"></i> 대회 페이지로 이동
                </a>
            </div>
        @endif
        
        <!-- 최근 접수된 영상 페이지네이션 -->
        @if($recentSubmissions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $recentSubmissions->appends(request()->query())->links('custom.pagination') }}
        </div>
        @endif
    </div>
</div>

{{-- 2차 예선 관리 - 2차 예선진출 기능이 필요 없어서 주석처리
<div class="card admin-card mb-4">
    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0 text-white">
            <i class="bi bi-trophy"></i> 
            2차 예선 관리
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- 2차 예선 진출자 선정 -->
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-primary">
                            <i class="bi bi-award"></i> 2차 예선 진출자 선정
                        </h6>
                        <p class="text-muted mb-2">
                            각 심사위원별로 상위 10명을 자동 선정합니다.
                        </p>
                        <ul class="text-muted small">
                            <li>심사위원: {{ $judgesCount }}명</li>
                            <li>완료된 심사: {{ $evaluatedSubmissions }}개</li>
                            <li>예상 진출자: 최대 {{ $judgesCount * 10 }}명</li>
                        </ul>
                    </div>
                    <div>
                        <form action="{{ route('admin.qualify.second.round') }}" method="POST" 
                              onsubmit="return confirm('각 심사위원별로 상위 10명을 2차 예선 진출자로 선정하시겠습니까?')">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-star-fill"></i> 진출자 선정
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 전체 학생 순위 조회 -->
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-primary">
                            <i class="bi bi-trophy"></i> 전체 학생 순위 조회
                        </h6>
                        <p class="text-muted mb-2">
                            모든 학생들의 종합 점수 순위를 확인합니다.
                        </p>
                        {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                        @php
                            $qualifiedCount = \App\Models\Evaluation::where('qualification_status', 'qualified')->count();
                        @endphp
                        <ul class="text-muted small">
                            <li>현재 진출자: {{ $qualifiedCount }}명</li>
                            <li>심사위원별 순위 표시</li>
                            <li>엑셀 다운로드 가능</li>
                        </ul>
                        --}}
                        <!-- <ul class="text-muted small">
                            <li>전체 학생 순위 표시</li>
                            <li>두 심사위원 점수 합계 기준</li>
                            <li>엑셀 다운로드 가능</li>
                        </ul> -->
                    </div>
                    <div>
                        {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                        <a href="{{ route('admin.second.round.qualifiers') }}" class="btn btn-success">
                            <i class="bi bi-eye"></i> 목록 보기
                        </a>
                        --}}
                        <a href="{{ route('admin.statistics') }}" class="btn btn-primary">
                            <i class="bi bi-bar-chart"></i> 전체 순위 보기
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 자격 상태 초기화 (2차 예선진출 기능이 필요 없어서 주석처리)
        <div class="row mt-3 pt-3 border-top">
            <div class="col-md-8">
                <h6 class="text-warning">
                    <i class="bi bi-arrow-clockwise"></i> 자격 상태 초기화
                </h6>
                <p class="text-muted mb-0">
                    모든 2차 예선 자격 상태를 초기화합니다.
                    <br><small class="text-warning">※ 선정된 진출자가 모두 대기 상태로 변경됩니다.</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <form action="{{ route('admin.reset.qualification') }}" method="POST" 
                      onsubmit="return confirm('모든 2차 예선 자격 상태를 초기화하시겠습니까? 이 작업은 되돌릴 수 없습니다.')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise"></i> 상태 초기화
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div> 
--}}

<!-- 주석 종료 -->

<!-- 위험 구역 -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card admin-card mb-5 my-4">
    <!-- <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
        <h6 class="mb-0 text-white">
            <i class="bi bi-exclamation-triangle"></i> 
            위험 구역
        </h6>
    </div> -->
    <div class="card-body py-4">
        <div class="alert alert-danger mb-4">
            <h6 class="alert-heading fs-6">
                <i class="bi bi-shield-exclamation"></i> 주의사항
            </h6>
            <p class="mb-0 small">
                아래 기능들은 시스템에 중대한 영향을 미칠 수 있습니다. 
                <strong>실행 전 반드시 백업하고 신중하게 검토하세요.</strong>
            </p>
        </div>
        
        <div class="d-grid gap-2">
            <a href="{{ route('admin.reset.confirmation') }}" class="btn btn-danger btn-sm py-1">
                <i class="bi bi-trash"></i> 전체 데이터 초기화
            </a>
        </div>
        </div>
    </div>
</div>



@push('scripts')
<script>
function clearSystemCache() {
    if (confirm('시스템 캐시를 정리하시겠습니까?')) {
        fetch('/admin/clear-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('캐시가 성공적으로 정리되었습니다.');
                location.reload();
            } else {
                alert('캐시 정리 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        });
    }
}

function optimizeSystem() {
    if (confirm('시스템 최적화를 실행하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 실행 중...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = '<i class="bi bi-check"></i> 완료!';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }, 3000);
    }
}

function manageLogs() {
    if (confirm('로그 파일을 정리하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 정리 중...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = '<i class="bi bi-check"></i> 완료!';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }, 2000);
    }
}
</script>
@endpush



@endsection