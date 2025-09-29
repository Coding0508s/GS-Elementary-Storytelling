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
                <h3 class="text-primary">{{ number_format($totalAssigned) }}</h3>
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
                <h3 class="text-warning">{{ number_format($pendingCount) }}</h3>
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
                <h3 class="text-info">{{ number_format($inProgressCount) }}</h3>
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
                <h3 class="text-success">{{ number_format($completedCount) }}</h3>
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
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-camera-video"></i> 배정된 영상 목록</h5>
    </div>
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
                            <th>AI 점수</th>
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
                                    <span class="badge bg-primary">
                                            <i class="bi bi-clock" style="color:rgb(253, 253, 253);"></i> 배정됨
                                    </span>
                                @elseif($assignment->status === 'in_progress')
                                    <span class="badge bg-danger">
                                            <i class="bi bi-arrow-clockwise" style="color:rgb(255, 255, 255);"></i> 심사중
                                    </span>
                                @elseif($assignment->status === 'completed')
                                    <span class="badge badge-evaluated">
                                        <i class="bi bi-check-circle" style="color:rgb(255, 255, 255);"></i> 완료
                                    </span>
                                @endif
                            </td>
                            <td>    
                                @if($assignment->evaluation)
                                    <span class="fw-bold text-success">{{ $assignment->evaluation->total_score }}</span>
                                    <small class="text-muted">/ 70</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $aiEvaluation = $assignment->videoSubmission->aiEvaluations->where('admin_id', $judge->id)->first();
                                @endphp
                                @if($aiEvaluation && $aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-info">{{ $aiEvaluation->total_score }}</span>
                                    <small class="text-muted">/ 30</small>
                                @elseif($aiEvaluation && $aiEvaluation->processing_status === 'processing')
                                    <span class="badge bg-warning">
                                        <i class="bi bi-arrow-clockwise"></i> 처리중
                                    </span>
                                @elseif($aiEvaluation && $aiEvaluation->processing_status === 'failed')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> 실패
                                    </span>
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
                                    
                                    <!-- AI 평가 버튼 -->
                                    @php
                                        $aiEval = $assignment->videoSubmission->aiEvaluations->where('admin_id', $judge->id)->first();
                                    @endphp
                                    @if(!$aiEval || $aiEval->processing_status === 'failed')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info mt-1 ai-evaluate-btn"
                                                data-assignment-id="{{ $assignment->id }}"
                                                title="AI로 평가하기">
                                            <i class="bi bi-robot"></i> AI 평가
                                        </button>
                                    @elseif($aiEval->processing_status === 'processing')
                                        <button type="button" 
                                                class="btn btn-sm btn-warning mt-1" 
                                                disabled>
                                            <i class="bi bi-arrow-clockwise"></i> 처리중...
                                        </button>
                                    @elseif($aiEval->processing_status === 'completed')
                                        <button type="button" 
                                                class="btn btn-sm btn-success mt-1 view-ai-result-btn"
                                                data-ai-evaluation-id="{{ $aiEval->id }}"
                                                title="AI 평가 결과 보기">
                                            <i class="bi bi-check-circle"></i> 결과 보기
                                        </button>
                                    @endif
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

    // AI 평가 버튼 클릭 이벤트
    document.addEventListener('click', function(e) {
        if (e.target.closest('.ai-evaluate-btn')) {
            e.preventDefault();
            const button = e.target.closest('.ai-evaluate-btn');
            const assignmentId = button.dataset.assignmentId;
            
            if (confirm('AI 평가를 시작하시겠습니까? 처리에 시간이 걸릴 수 있습니다.')) {
                // 버튼 비활성화 및 로딩 상태 표시
                button.disabled = true;
                button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 처리중...';
                button.classList.remove('btn-outline-info');
                button.classList.add('btn-warning');
                
                // AI 평가 요청
                fetch(`{{ url('/judge/ai-evaluate') }}/${assignmentId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 성공 시 버튼을 "결과 보기"로 변경 (개선된 방식)
                        updateAiButtonState(button, {
                            id: data.ai_evaluation_id,
                            status: 'completed'
                        });
                        
                        alert('AI 평가가 완료되었습니다! "결과 보기" 버튼을 클릭하여 결과를 확인하세요.');
                    } else {
                        alert('AI 평가 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
                        // 버튼 복원
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
                        button.classList.remove('btn-warning');
                        button.classList.add('btn-outline-info');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('AI 평가 요청 중 오류가 발생했습니다.');
                    // 버튼 복원
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
                    button.classList.remove('btn-warning');
                    button.classList.add('btn-outline-info');
                });
            }
        }
        
        // AI 결과 보기 버튼 클릭 이벤트
        if (e.target.closest('.view-ai-result-btn')) {
            e.preventDefault();
            const button = e.target.closest('.view-ai-result-btn');
            const aiEvaluationId = button.dataset.aiEvaluationId;
            
            // AI 평가 결과 모달 표시
            showAiResultModal(aiEvaluationId);
        }
    });

    // AI 평가 결과 모달 표시 함수
    function showAiResultModal(aiEvaluationId) {
        fetch(`{{ url('/judge/ai-result') }}/${aiEvaluationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = createAiResultModal(data.aiEvaluation);
                    document.body.appendChild(modal);
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    
                    // 모달 닫힐 때 DOM에서 제거
                    modal.addEventListener('hidden.bs.modal', function() {
                        document.body.removeChild(modal);
                    });
                } else {
                    alert('AI 평가 결과를 불러올 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('AI 평가 결과 조회 중 오류가 발생했습니다.');
            });
    }

    // AI 버튼 상태 업데이트 함수 (video-list용)
    function updateAiButtonState(button, aiEvalData) {
        if (aiEvalData.status === 'completed') {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check-circle"></i> 결과 보기';
            button.classList.remove('btn-warning', 'ai-evaluate-btn', 'btn-outline-info');
            button.classList.add('btn-success', 'view-ai-result-btn');
            button.dataset.aiEvaluationId = aiEvalData.id;
            button.title = 'AI 평가 결과 보기';
            
            // 클래스명 확인으로 중복 변경 방지
            if (!button.classList.contains('view-ai-result-btn')) {
                button.classList.add('view-ai-result-btn');
            }
            
            console.log('AI 버튼 상태 업데이트 완료:', aiEvalData.status, button.className);
        } else if (aiEvalData.status === 'processing') {
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 처리중...';
            button.classList.remove('btn-outline-info', 'btn-success', 'view-ai-result-btn');
            button.classList.add('btn-warning');
        } else if (aiEvalData.status === 'failed') {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
            button.classList.remove('btn-warning', 'btn-success', 'view-ai-result-btn');
            button.classList.add('btn-outline-info', 'ai-evaluate-btn');
            button.title = 'AI로 평가하기';
        }
    }

    // AI 결과 모달 생성 함수
    function createAiResultModal(aiEvaluation) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-robot"></i> AI 평가 결과
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">발음 및 전달력</h6>
                                        <h3 class="text-primary">${aiEvaluation.pronunciation_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">어휘 및 표현</h6>
                                        <h3 class="text-success">${aiEvaluation.vocabulary_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">유창성</h6>
                                        <h3 class="text-info">${aiEvaluation.fluency_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">총점</h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-primary">${aiEvaluation.total_score}/30</h2>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">AI 심사평</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${aiEvaluation.ai_feedback || '심사평이 없습니다.'}</p>
                            </div>
                        </div>
                        
                        ${aiEvaluation.transcription ? `
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">음성 전사 결과</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0" style="font-family: monospace; font-size: 0.9em;">${aiEvaluation.transcription}</p>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        `;
        return modal;
    }
});
</script>
@endsection 