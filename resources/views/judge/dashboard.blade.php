@extends('admin.layout')

@section('title', '심사위원 대시보드')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-person-badge"></i> 심사위원 대시보드</h1>
    <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalAssigned) }}</h3>
                <p class="card-text text-muted">배정된 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($pendingEvaluations) }}</h3>
                <p class="card-text text-muted">심사 대기</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-arrow-clockwise"></i>
                </div>
                <h3 class="text-info">{{ number_format($inProgressEvaluations) }}</h3>
                <p class="card-text text-muted">심사 진행중</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($completedEvaluations) }}</h3>
                <p class="card-text text-muted">심사 완료</p>
            </div>
        </div>
    </div>
</div>

<!-- 진행률 -->
@if($totalAssigned > 0)
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> 심사 진행률</h5>
    </div>
    <div class="card-body">
        @php
            $progressPercentage = round(($completedEvaluations / $totalAssigned) * 100, 1);
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
            <div class="col-4 text-center">
                <small class="text-muted">심사 완료</small><br>
                <strong class="text-success">{{ $completedEvaluations }}개</strong>
            </div>
            <div class="col-4 text-center">
                <small class="text-muted">심사 진행중</small><br>
                <strong class="text-info">{{ $inProgressEvaluations }}개</strong>
            </div>
            <div class="col-4 text-center">
                <small class="text-muted">심사 대기</small><br>
                <strong class="text-warning">{{ $pendingEvaluations }}개</strong>
            </div>
        </div>
    </div>
</div>
@endif

<!-- 빠른 작업 -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> 빠른 작업</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('judge.video.list') }}" 
                       class="btn btn-admin">
                        <i class="bi bi-list-ul"></i> 모든 영상 보기
                    </a>
                    
                    @if($pendingEvaluations > 0)
                    <a href="{{ route('judge.video.list') }}?status=assigned" 
                       class="btn btn-admin">
                        <i class="bi bi-play-circle"></i> 대기 영상 심사 시작
                    </a>
                    @endif
                    
                    @if($inProgressEvaluations > 0)
                    <a href="{{ route('judge.video.list') }}?status=in_progress" 
                       class="btn btn-admin">
                        <i class="bi bi-arrow-clockwise"></i> 진행중인 심사 계속
                    </a>
                    @endif
                    
                    <a href="{{ route('admin.dashboard') }}" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-gear"></i> 관리자 페이지로 이동
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> 심사위원 정보</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-person text-primary"></i>
                        <strong>심사위원:</strong> {{ $judge->name }}
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-envelope text-info"></i>
                        <strong>이메일:</strong> {{ $judge->email }}
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-calendar text-success"></i>
                        <strong>배정된 영상:</strong> {{ $totalAssigned }}개
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-warning"></i>
                        <strong>완료율:</strong> {{ $totalAssigned > 0 ? round(($completedEvaluations / $totalAssigned) * 100, 1) : 0 }}%
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 내가 심사한 영상 순위 -->
@if($myEvaluatedRankings->count() > 0)
<div class="card admin-card mb-4">
    <!-- <div class="card-header"> -->
        <h4 class="mb-0 mt-3 mx-4"><i class="bi bi-trophy"></i> 내가 심사한 영상 순위 (상위 10명)</h4>
        <small class="text-muted mx-4">※ 동점 시 업로드 빠른 순으로 순위 결정</small>
    <!-- </div> -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-admin table-hover">
                <thead>
                    <tr>
                        <th width="80px">순위</th>
                        <th>학생명</th>
                        <th>기관</th>
                        <th>학년</th>
                        <th>총점</th>
                        <th>등급</th>
                        <th>업로드시간</th>
                        <th>상태</th>
                        <th>다시보기</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myEvaluatedRankings as $ranking)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($ranking['rank'] <= 3)
                                    @if($ranking['rank'] == 1)
                                        <i class="bi bi-trophy-fill text-warning me-2" style="font-size: 1.2rem;"></i>
                                    @elseif($ranking['rank'] == 2)
                                        <i class="bi bi-award-fill text-secondary me-2" style="font-size: 1.2rem;"></i>
                                    @else
                                        <i class="bi bi-award-fill text-warning me-2" style="font-size: 1.2rem;"></i>
                                    @endif
                                @endif
                                <strong class="text-primary">{{ $ranking['rank'] }}위</strong>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $ranking['student_name'] }}</strong>
                        </td>
                        <td>{{ $ranking['institution'] }}</td>
                        <td>{{ $ranking['grade'] }}</td>
                        <td>
                            <div>
                                <span class="fw-bold text-primary">{{ $ranking['total_score'] }}</span>
                                <small class="text-muted">/80점 (총합)</small>
                            </div>
                            <div>
                                <span class="fw-bold text-secondary">{{ $ranking['my_score'] }}</span>
                                <small class="text-muted">/40점 (내 평가)</small>
                            </div>
                        </td>
                        <td>
                            @php
                                $badgeClass = '';
                                switch($ranking['evaluation_grade']) {
                                    case '우수': $badgeClass = 'bg-success'; break;
                                    case '양호': $badgeClass = 'bg-primary'; break;
                                    case '보통': $badgeClass = 'bg-info'; break;
                                    case '미흡': $badgeClass = 'bg-warning text-dark'; break;
                                    default: $badgeClass = 'bg-danger'; break;
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $ranking['evaluation_grade'] }}</span>
                        </td>
                        <td>
                            <small class="text-info">{{ $ranking['upload_time'] }}</small>
                        </td>
                        <td>
                            {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                            @php
                                $qualificationStatus = \App\Models\Evaluation::where('video_submission_id', $ranking['submission_id'])
                                    ->where('admin_id', auth()->guard('admin')->user()->id)
                                    ->value('qualification_status');
                            @endphp
                            @if($qualificationStatus === 'qualified')
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> 진출
                                </span>
                            @elseif($qualificationStatus === 'not_qualified')
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> 탈락
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-clock"></i> 대기
                                </span>
                            @endif
                            --}}
                            <span class="badge bg-primary">
                                <i class="bi bi-check2-all"></i> 평가완료
                            </span>
                        </td>
                        <td>
                            @if($ranking['assignment_id'])
                                <a href="{{ route('judge.evaluation.edit', $ranking['assignment_id']) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> 보기
                                </a>
                            @else
                                <span class="btn btn-sm btn-outline-secondary disabled">
                                    <i class="bi bi-exclamation-triangle"></i> 배정 없음
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($completedEvaluations > 10)
        <div class="text-center mt-3">
            <small class="text-muted">
                총 {{ $completedEvaluations }}개 중 상위 10명을 표시하고 있습니다.
            </small>
        </div>
        @endif
    </div>
</div>
@endif

<!-- 최근 배정된 영상 -->
<div class="card admin-card">
    <!-- <div class="card-header d-flex justify-content-between align-items-center"> -->
        <h5 class="mb-0 mt-3 mx-4"><i class="bi bi-clock-history"></i> 최근 배정된 영상</h5>
    <!-- </div> -->
    <div class="card-body">
        @if($recentAssignments->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>배정일</th>
                            <th>학생명</th>
                            <th>기관</th>
                            <th>상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentAssignments as $assignment)
                        <tr>
                            <td>
                                <small>{{ $assignment->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $assignment->videoSubmission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $assignment->videoSubmission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $assignment->videoSubmission->institution_name }}<br>
                                <small class="text-muted">{{ $assignment->videoSubmission->class_name }}</small>
                            </td>
                            <td>
                                @if($assignment->status === 'assigned')
                                    <span class="badge badge-pending" style="color:rgb(3, 116, 254);">
                                        <i class="bi bi-clock"></i> 배정됨
                                    </span>
                                @elseif($assignment->status === 'in_progress')
                                    <span class="badge badge-info" style="color:rgb(226, 41, 4);">
                                        <i class="bi bi-arrow-clockwise"></i> 심사중
                                    </span>
                                @elseif($assignment->status === 'completed')
                                    <span class="badge" style="color:rgb(120, 223, 69);">
                                        <i class="bi bi-check-circle"></i> 완료
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($assignment->status === 'assigned')
                                    <form action="{{ route('judge.evaluation.start', $assignment->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-play-circle"></i> 심사 시작
                                        </button>
                                    </form>
                                @elseif($assignment->status === 'in_progress')
                                    <a href="{{ route('judge.evaluation.show', $assignment->id) }}" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-arrow-clockwise"></i> 심사 계속
                                    </a>
                                @elseif($assignment->status === 'completed')
                                    <a href="{{ route('judge.evaluation.edit', $assignment->id) }}" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-pencil"></i> 수정
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2">배정된 영상이 없습니다.</p>
                <p class="text-muted">새로운 영상이 배정되면 여기에 표시됩니다.</p>
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