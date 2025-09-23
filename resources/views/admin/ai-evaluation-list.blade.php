@extends('admin.layout')

@section('title', 'AI 채점 결과')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-robot"></i> AI 채점 결과</h1>
        <p class="text-muted mb-0">OpenAI Whisper와 GPT를 활용한 자동 채점 결과입니다.</p>
    </div>
    <div>
        @if($totalEvaluations > 0)
        <button type="button" class="btn btn-danger me-2" id="reset-ai-evaluations-btn">
            <i class="bi bi-trash3"></i> 전체 초기화 ({{ number_format($totalEvaluations) }}개)
        </button>
        @else
        <button type="button" class="btn btn-secondary me-2" disabled>
            <i class="bi bi-trash3"></i> 초기화할 데이터 없음
        </button>
        @endif
        <a href="{{ route('admin.ai-evaluations.export') }}" class="btn btn-success me-2" id="excel-download-btn">
            <i class="bi bi-file-earmark-excel"></i> Excel 다운로드
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
        </a>
    </div>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-robot"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalEvaluations) }}</h3>
                <p class="card-text text-muted">전체 AI 평가</p>
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
                <p class="card-text text-muted">완료된 평가</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-arrow-clockwise"></i>
                </div>
                <h3 class="text-warning">{{ number_format($processingEvaluations) }}</h3>
                <p class="card-text text-muted">처리중인 평가</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-calculator"></i>
                </div>
                <h3 class="text-info">{{ number_format($averageScore, 1) }}</h3>
                <p class="card-text text-muted">평균 점수</p>
            </div>
        </div>
    </div>
</div>

<!-- 필터 버튼 -->
<div class="mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all">
            전체
        </button>
        <button type="button" class="btn btn-outline-success filter-btn" data-filter="completed">
            완료 ({{ $completedEvaluations }})
        </button>
        <button type="button" class="btn btn-outline-warning filter-btn" data-filter="processing">
            처리중 ({{ $processingEvaluations }})
        </button>
        <button type="button" class="btn btn-outline-danger filter-btn" data-filter="failed">
            실패 ({{ $failedEvaluations }})
        </button>
    </div>
</div>

<!-- AI 평가 결과 테이블 -->
<div class="card">
    <div class="card-body">
        @if($aiEvaluations->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>학생명</th>
                            <th>학교/학년</th>
                            <th>평가자</th>
                            <th>발음</th>
                            <th>어휘</th>
                            <th>유창성</th>
                            <th>총점</th>
                            <th>상태</th>
                            <th>평가일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aiEvaluations as $aiEvaluation)
                        <tr class="evaluation-row" data-status="{{ $aiEvaluation->processing_status }}">
                            <td>
                                <strong>{{ $aiEvaluation->videoSubmission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $aiEvaluation->videoSubmission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $aiEvaluation->videoSubmission->institution_name }}<br>
                                <small class="text-muted">{{ $aiEvaluation->videoSubmission->grade }}</small>
                            </td>
                            <td>
                                {{ $aiEvaluation->admin->name }}<br>
                                <small class="text-muted">{{ $aiEvaluation->admin->position ?? '심사위원' }}</small>
                            </td>
                            <td class="text-center">
                                @if($aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-primary">{{ $aiEvaluation->pronunciation_score }}</span>
                                    <small class="text-muted">/10</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-success">{{ $aiEvaluation->vocabulary_score }}</span>
                                    <small class="text-muted">/10</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-info">{{ $aiEvaluation->fluency_score }}</span>
                                    <small class="text-muted">/10</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-primary">{{ $aiEvaluation->total_score }}</span>
                                    <small class="text-muted">/30</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($aiEvaluation->processing_status === 'completed')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> 완료
                                    </span>
                                @elseif($aiEvaluation->processing_status === 'processing')
                                    <span class="badge bg-warning">
                                        <i class="bi bi-arrow-clockwise"></i> 처리중
                                    </span>
                                @elseif($aiEvaluation->processing_status === 'failed')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> 실패
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-clock"></i> 대기
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $aiEvaluation->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm" role="group">
                                    @if($aiEvaluation->processing_status === 'completed')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info view-ai-detail-btn"
                                                data-ai-evaluation-id="{{ $aiEvaluation->id }}"
                                                title="AI 평가 상세 보기">
                                            <i class="bi bi-eye"></i> 상세
                                        </button>
                                    @elseif($aiEvaluation->processing_status === 'failed')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger view-error-btn"
                                                data-error-message="{{ $aiEvaluation->error_message }}"
                                                title="오류 메시지 보기">
                                            <i class="bi bi-exclamation-triangle"></i> 오류
                                        </button>
                                    @endif
                                    
                                    <!-- 비디오 보기 버튼 -->
                                    <a href="{{ route('admin.video.view', $aiEvaluation->videoSubmission->id) }}" 
                                       class="btn btn-sm btn-outline-secondary mt-1"
                                       target="_blank"
                                       title="영상 보기">
                                        <i class="bi bi-play-circle"></i> 영상
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            @if($aiEvaluations->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $aiEvaluations->links('custom.pagination') }}
            </div>
            @endif
        @else
            <div class="text-center py-4">
                <i class="bi bi-robot display-4 text-muted"></i>
                <p class="text-muted mt-2">아직 AI 평가 결과가 없습니다.</p>
                <p class="text-muted">심사위원이 AI 평가를 실행하면 여기에 표시됩니다.</p>
            </div>
        @endif
    </div>
</div>

<style>
.stats-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.stats-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.table-admin thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

.evaluation-row {
    transition: background-color 0.15s ease-in-out;
}

.evaluation-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.filter-btn.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 필터 버튼 이벤트
    const filterButtons = document.querySelectorAll('.filter-btn');
    const evaluationRows = document.querySelectorAll('.evaluation-row');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // 활성 버튼 스타일 업데이트
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-danger');
            });
            
            this.classList.add('active');
            this.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-danger');
            this.classList.add('btn-primary');

            // 행 필터링
            evaluationRows.forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // AI 평가 상세 보기 버튼 클릭 이벤트
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-ai-detail-btn')) {
            e.preventDefault();
            const button = e.target.closest('.view-ai-detail-btn');
            const aiEvaluationId = button.dataset.aiEvaluationId;
            
            showAiDetailModal(aiEvaluationId);
        }
        
        // 오류 메시지 보기 버튼 클릭 이벤트
        if (e.target.closest('.view-error-btn')) {
            e.preventDefault();
            const button = e.target.closest('.view-error-btn');
            const errorMessage = button.dataset.errorMessage;
            
            alert('오류 메시지:\n' + errorMessage);
        }
    });

    // AI 평가 상세 모달 표시 함수
    function showAiDetailModal(aiEvaluationId) {
        fetch(`{{ url('/admin/ai-evaluation') }}/${aiEvaluationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = createAiDetailModal(data.aiEvaluation);
                    document.body.appendChild(modal);
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    
                    // 모달 닫힐 때 DOM에서 제거
                    modal.addEventListener('hidden.bs.modal', function() {
                        document.body.removeChild(modal);
                    });
                } else {
                    alert('AI 평가 상세 정보를 불러올 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('AI 평가 상세 정보 조회 중 오류가 발생했습니다.');
            });
    }

    // AI 상세 모달 생성 함수
    function createAiDetailModal(aiEvaluation) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-robot"></i> AI 평가 상세 정보
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>학생 정보</h6>
                                <p><strong>이름:</strong> ${aiEvaluation.video_submission.student_name_korean} (${aiEvaluation.video_submission.student_name_english})</p>
                                <p><strong>학교:</strong> ${aiEvaluation.video_submission.school_name}</p>
                                <p><strong>학년:</strong> ${aiEvaluation.video_submission.grade}</p>
                                <p><strong>과제:</strong> ${aiEvaluation.video_submission.required_task || '-'}</p>
                                <p><strong>질문:</strong> ${aiEvaluation.video_submission.selected_question || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>평가 정보</h6>
                                <p><strong>평가자:</strong> ${aiEvaluation.judge.name}</p>
                                <p><strong>평가일:</strong> ${new Date(aiEvaluation.created_at).toLocaleString('ko-KR')}</p>
                                <p><strong>상태:</strong> ${getStatusBadge(aiEvaluation.status)}</p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">발음 및 전달력</h6>
                                        <h3 class="text-primary">${aiEvaluation.pronunciation_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">어휘 및 표현</h6>
                                        <h3 class="text-success">${aiEvaluation.vocabulary_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">유창성</h6>
                                        <h3 class="text-info">${aiEvaluation.fluency_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">총점</h6>
                                        <h3 class="text-primary">${aiEvaluation.total_score}/30</h3>
                                    </div>
                                </div>
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
                                <p class="mb-0" style="font-family: monospace; font-size: 0.9em; white-space: pre-wrap;">${aiEvaluation.transcription}</p>
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

    function getStatusBadge(status) {
        switch(status) {
            case 'completed':
                return '<span class="badge bg-success">완료</span>';
            case 'processing':
                return '<span class="badge bg-warning">처리중</span>';
            case 'failed':
                return '<span class="badge bg-danger">실패</span>';
            default:
                return '<span class="badge bg-secondary">대기</span>';
        }
    }

    // AI 채점 결과 초기화 기능
    document.getElementById('reset-ai-evaluations-btn').addEventListener('click', function() {
        if (confirm('⚠️ 경고: 모든 AI 채점 결과가 영구적으로 삭제됩니다.\n\n이 작업은 되돌릴 수 없습니다. 정말로 초기화하시겠습니까?')) {
            if (confirm('🔴 최종 확인: 정말로 모든 AI 채점 결과를 삭제하시겠습니까?\n\n삭제된 데이터는 복구할 수 없습니다.')) {
                resetAiEvaluations();
            }
        }
    });

    function resetAiEvaluations() {
        const button = document.getElementById('reset-ai-evaluations-btn');
        const originalText = button.innerHTML;
        
        // 버튼 비활성화 및 로딩 상태
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 초기화 중...';
        
        fetch('{{ route("admin.ai-evaluations.reset") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 성공 메시지 표시
                alert(`✅ 초기화 완료!\n\n총 ${data.deleted_count}개의 AI 채점 결과가 삭제되었습니다.`);
                
                // 페이지 새로고침
                location.reload();
            } else {
                alert('❌ 초기화 실패: ' + (data.message || '알 수 없는 오류가 발생했습니다.'));
                
                // 버튼 복원
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ 초기화 중 오류가 발생했습니다.');
            
            // 버튼 복원
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
});
</script>
@endsection