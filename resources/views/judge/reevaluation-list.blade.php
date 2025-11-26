@extends('admin.layout')

@section('title', '재평가 대상 영상 목록')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-arrow-repeat"></i> 재평가 대상 영상 목록</h1>
        <p class="text-muted mb-0">{{ $judge->name }} 심사위원님에게 배정된 재평가 대상 영상들입니다.</p>
        <div class="alert alert-info mt-2 mb-0">
            <i class="bi bi-info-circle"></i> <strong>재평가 안내</strong>
            <ul class="mb-0 mt-2">
                <li>기존 평가 기록과 AI 평가는 유지되며, 새로운 재평가를 진행할 수 있습니다.</li>
                <li><strong class="text-warning">기존에 평가한 영상은 재평가할 수 없습니다.</strong> 재평가는 기존 평가가 없는 영상에 대해서만 가능합니다.</li>
                <li>재평가를 완료하면 새로운 평가 결과가 생성되며, 원본 평가는 그대로 유지됩니다.</li>
            </ul>
        </div>
    </div>
    <a href="{{ route('judge.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
    </a>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalReevaluation) }}</h3>
                <p class="card-text text-muted">전체 재평가 대상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($pendingReevaluation) }}</h3>
                <p class="card-text text-muted">재평가 대기</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($completedReevaluation) }}</h3>
                <p class="card-text text-muted">재평가 완료</p>
            </div>
        </div>
    </div>
</div>

<!-- 영상 목록 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> 재평가 대상 영상 목록</h5>
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
                            <th>원본 평가</th>
                            <th>재평가 점수</th>
                            <th>합산 점수</th>
                            <th>AI 점수</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                        <tr>
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
                                @if($assignment->original_evaluation)
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-secondary mb-1">
                                            {{ $assignment->original_evaluation->total_score }}/70
                                        </span>
                                        <small class="text-muted">
                                            {{ $assignment->original_evaluation->created_at->format('Y-m-d H:i') }}
                                        </small>
                                        @if($assignment->original_evaluation->comments)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary mt-1" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top"
                                                    title="{{ $assignment->original_evaluation->comments }}">
                                                <i class="bi bi-chat-left-text"></i> 코멘트
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">평가 이력 없음</span>
                                @endif
                            </td>
                            <td>
                                @if($assignment->reevaluation)
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="fw-bold text-success mb-1">
                                            {{ $assignment->reevaluation->total_score }}/70
                                        </span>
                                        <small class="text-info">재평가 완료</small>
                                        <small class="text-muted">
                                            {{ $assignment->reevaluation->created_at->format('Y-m-d H:i') }}
                                        </small>
                                    </div>
                                @elseif($assignment->original_evaluation)
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="text-muted mb-1">재평가 미완료</span>
                                        <small class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i> 원본 평가만 존재
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $originalScore = $assignment->original_evaluation ? $assignment->original_evaluation->total_score : 0;
                                    $reevaluationScore = $assignment->reevaluation ? $assignment->reevaluation->total_score : 0;
                                    $totalCombinedScore = $originalScore + $reevaluationScore;
                                @endphp
                                @if($assignment->reevaluation || $assignment->original_evaluation)
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-primary fs-6 mb-1">
                                            {{ number_format($totalCombinedScore) }}/{{ number_format(($assignment->original_evaluation ? 70 : 0) + ($assignment->reevaluation ? 70 : 0)) }}
                                        </span>
                                        @if($assignment->original_evaluation && $assignment->reevaluation)
                                            <small class="text-muted">
                                                원본 {{ $originalScore }} + 재평가 {{ $reevaluationScore }}
                                            </small>
                                        @elseif($assignment->original_evaluation)
                                            <small class="text-muted">
                                                원본 {{ $originalScore }}
                                            </small>
                                        @elseif($assignment->reevaluation)
                                            <small class="text-muted">
                                                재평가 {{ $reevaluationScore }}
                                            </small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $aiEvaluation = $assignment->admin_ai_evaluation ?? 
                                                   $assignment->videoSubmission->aiEvaluations->where('admin_id', '!=', $judge->id)->first();
                                @endphp
                                @if($aiEvaluation && $aiEvaluation->processing_status === 'completed')
                                    <span class="fw-bold text-info">{{ $aiEvaluation->total_score }}</span>
                                    <small class="text-muted">/ 30</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm" role="group">
                                    @if($assignment->reevaluation)
                                        <a href="{{ route('judge.evaluation.edit', $assignment->id) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-pencil"></i> 재평가 수정
                                        </a>
                                    @elseif($assignment->original_evaluation)
                                        {{-- 기존 평가가 있는 경우 재평가 불가 --}}
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                disabled
                                                title="기존에 평가한 영상은 재평가할 수 없습니다.">
                                            <i class="bi bi-lock"></i> 재평가 불가
                                        </button>
                                        <small class="text-warning mt-1">
                                            <i class="bi bi-exclamation-triangle"></i> 기존 평가 있음
                                        </small>
                                    @else
                                        <a href="{{ route('judge.evaluation.show', $assignment->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-play-circle"></i> 재평가 시작
                                        </a>
                                    @endif
                                    
                                    <!-- 영상 다운로드 버튼 -->
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
                <p class="text-muted mt-2">재평가 대상 영상이 없습니다.</p>
                <p class="text-muted">관리자가 재평가 대상을 지정하면 여기에 표시됩니다.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 툴팁 초기화
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
@endsection

