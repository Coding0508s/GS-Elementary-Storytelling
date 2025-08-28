@extends('admin.layout')

@section('title', '배정된 영상 목록')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-list-ul"></i> 배정된 영상 목록</h1>
        <p class="text-muted mb-0">{{ $judge->name }} 심사위원님에게 배정된 영상들입니다.</p>
    </div>
    <a href="{{ route('judge.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
    </a>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($assignments->count()) }}</h3>
                <p class="card-text text-muted">전체 배정</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($assignments->where('status', 'assigned')->count()) }}</h3>
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
                <h3 class="text-info">{{ number_format($assignments->where('status', 'in_progress')->count()) }}</h3>
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
                <h3 class="text-success">{{ number_format($assignments->where('status', 'completed')->count()) }}</h3>
                <p class="card-text text-muted">심사 완료</p>
            </div>
        </div>
    </div>
</div>

<!-- 필터 버튼 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> 상태별 필터</h5>
    </div>
    <div class="card-body">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary filter-btn active" data-filter="all">
                <i class="bi bi-collection"></i> 전체
            </button>
            <button type="button" class="btn btn-outline-warning filter-btn" data-filter="assigned">
                <i class="bi bi-clock"></i> 배정됨
            </button>
            <button type="button" class="btn btn-outline-info filter-btn" data-filter="in_progress">
                <i class="bi bi-arrow-clockwise"></i> 심사중
            </button>
            <button type="button" class="btn btn-outline-success filter-btn" data-filter="completed">
                <i class="bi bi-check-circle"></i> 완료
            </button>
        </div>
    </div>
</div>

<!-- 영상 목록 -->
<div class="card admin-card">
    <!-- <div class="card-header"> -->
        <h5 class="mb-0 mt-3 mx-4"><i class="bi bi-camera-video"></i> 배정된 영상 목록</h5>
   <!--  </div> -->
    <div class="card-body">
        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>학생명</th>
                            <th>기관명</th>
                            <th>Unit 주제</th>
                            <th>배정일</th>
                            <th>상태</th>
                            <th>총점</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                        <tr class="assignment-row" data-status="{{ $assignment->status }}">
                            <td>
                                <strong>{{ $assignment->videoSubmission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $assignment->videoSubmission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $assignment->videoSubmission->institution_name }}<br>
                                <small class="text-muted">{{ $assignment->videoSubmission->class_name }}</small>
                            </td>
                            <td>
                                {{ $assignment->videoSubmission->unit_topic ?: '-' }}
                            </td>
                            <td>
                                <small>{{ $assignment->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                @if($assignment->status === 'assigned')
                                    <span class="badge badge-pending;">
                                            <i class="bi bi-clock" style="color:rgb(3, 116, 254);"></i> 배정됨
                                    </span>
                                @elseif($assignment->status === 'in_progress')
                                    <span class="badge badge-info" style="color:rgb(226, 41, 4);">
                                        <i class="bi bi-arrow-clockwise" style="color:rgb(226, 41, 4);"></i> 심사중
                                    </span>
                                @elseif($assignment->status === 'completed')
                                    <span class="badge badge-evaluated">
                                        <i class="bi bi-check-circle" style="color:rgb(79, 223, 7);"></i> 완료
                                    </span>
                                @endif
                            </td>
                            <td>    
                                @if($assignment->evaluation)
                                    <span class="fw-bold text-success">{{ $assignment->evaluation->total_score }}</span>
                                    <small class="text-muted">/ 40</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm" role="group">
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
                                    
                                    <!-- 영상 다운로드 버튼 (항상 표시) -->
                                    <a href="{{ route('judge.video.download', $assignment->id) }}" 
                                       class="btn btn-sm btn-outline-secondary mt-1"
                                       target="_blank"
                                       title="영상 다운로드">
                                        <i class="bi bi-download"></i> 다운로드
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            @if($assignments->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $assignments->links('custom.pagination') }}
            </div>
            @endif
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
    const filterBtns = document.querySelectorAll('.filter-btn');
    const assignmentRows = document.querySelectorAll('.assignment-row');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // 버튼 스타일 변경
            filterBtns.forEach(b => {
                b.classList.remove('active');
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary', 'btn-outline-warning', 'btn-outline-info', 'btn-outline-success');
            });
            this.classList.add('active');
            this.classList.remove('btn-outline-primary', 'btn-outline-warning', 'btn-outline-info', 'btn-outline-success');
            this.classList.add('btn-primary');

            // 행 필터링
            assignmentRows.forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>
@endsection 