@extends('layouts.app')

@section('title', '영상 심사')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-clipboard-check"></i> 영상 심사</h1>
        <p class="text-muted mb-0">{{ $judge->name }} 심사위원님</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('judge.video.list') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 목록으로
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> 다음 오류를 확인해주세요:
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- 학생 정보 -->
    <div class="col-md-6 mb-4">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> 학생 정보</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">학생명 (한글)</label>
                        <p class="fw-bold">{{ $submission->student_name_korean }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">학생명 (영어)</label>
                        <p class="fw-bold">{{ $submission->student_name_english }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">학년</label>
                        <p>{{ $submission->grade }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">나이</label>
                        <p>{{ $submission->age }}세</p>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label text-muted">거주 지역</label>
                        <p>{{ $submission->region }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">기관명</label>
                        <p>{{ $submission->institution_name }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">반 이름</label>
                        <p>{{ $submission->class_name }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">학부모 성함</label>
                        <p>{{ $submission->parent_name }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">연락처</label>
                        <p>{{ $submission->parent_phone }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 영상 정보 -->
    <div class="col-md-6 mb-4">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-camera-video"></i> 영상 정보</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">파일명</label>
                    <p class="fw-bold">{{ $submission->video_file_name }}</p>
                </div>
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">파일 형식</label>
                        <p>{{ strtoupper($submission->video_file_type) }}</p>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label text-muted">파일 크기</label>
                        <p>{{ $submission->getFormattedFileSizeAttribute() }}</p>
                    </div>
                </div>
                @if($submission->unit_topic)
                <div class="mb-3">
                    <label class="form-label text-muted">Unit 주제</label>
                    <p class="fw-bold text-primary">{{ $submission->unit_topic }}</p>
                </div>
                @endif
                <div class="mb-3">
                    <label class="form-label text-muted">업로드 일시</label>
                    <p>{{ $submission->created_at->format('Y년 m월 d일 H:i') }}</p>
                </div>
                
                <!-- 영상 플레이어 또는 다운로드 링크 -->
                <div class="mb-3">
                    <label class="form-label text-muted">영상 파일</label>
                    <div class="p-3 bg-light rounded">
                        <i class="bi bi-camera-video-fill text-primary fs-4"></i>
                        <p class="mb-2">영상 재생 또는 다운로드</p>
                        <small class="text-muted">
                            파일 경로: {{ $submission->video_file_path }}
                        </small>
                        <br>
                        <small class="text-info">
                            * 실제 운영 시 여기에 영상 플레이어나 다운로드 링크가 표시됩니다.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 심사 폼 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-star"></i> 심사 평가
            @if($assignment->evaluation)
                <span class="badge bg-success ms-2">수정 모드</span>
            @else
                <span class="badge bg-warning ms-2">신규 심사</span>
            @endif
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('judge.evaluation.store', $assignment->id) }}" method="POST" id="evaluation-form">
            @csrf
            
            <div class="row">
                @php
                    $criteriaLabels = [
                        'pronunciation_score' => '정확한 발음과 자연스러운 억양, 전달력',
                        'vocabulary_score' => '올바른 어휘 및 표현 사용',
                        'fluency_score' => '유창성 수준',
                        'confidence_score' => '자신감, 긍정적이고 밝은 태도'
                    ];
                @endphp
                
                @foreach($criteriaLabels as $field => $label)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">{{ $label }}</h6>
                            
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label">
                                    점수 (1-100점)
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" 
                                           class="form-range flex-grow-1" 
                                           id="{{ $field }}_range"
                                           min="1" 
                                           max="25" 
                                           step="1"
                                           value="{{ old($field, $assignment->evaluation->$field ?? 25) }}">
                                    <input type="number" 
                                           class="form-control score-input" 
                                           id="{{ $field }}"
                                           name="{{ $field }}"
                                           min="1" 
                                           max="100" 
                                           value="{{ old($field, $assignment->evaluation->$field ?? '') }}"
                                           required>
                                </div>
                            </div>
                            
                            <!-- 점수 가이드 -->
                            <div class="score-guide">
                                <small class="text-muted">
                                    <strong>점수 가이드:</strong><br>
                                    1-25: 미흡 | 26-50: 보통 | 51-75: 양호 | 76-100: 우수
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- 총점 표시 -->
            <div class="card bg-light mb-4">
                <div class="card-body text-center">
                    <h4 class="mb-0">
                        총점: <span id="total-score" class="text-primary fw-bold">
                            {{ $assignment->evaluation ? $assignment->evaluation->total_score : 0 }}
                        </span> / 100점
                    </h4>
                    <div class="mt-2">
                        <span id="grade-badge" class="badge fs-6">등급 계산 중...</span>
                    </div>
                </div>
            </div>
            
            <!-- 심사 코멘트 -->
            <div class="mb-4">
                <label for="comments" class="form-label">
                    <i class="bi bi-chat-dots"></i> 심사 코멘트 (선택사항)
                </label>
                <textarea class="form-control" 
                          id="comments" 
                          name="comments" 
                          rows="4"
                          placeholder="학생의 발표에 대한 구체적인 피드백을 입력해주세요...">{{ old('comments', $assignment->evaluation->comments ?? '') }}</textarea>
                <div class="form-text">
                    학생과 학부모에게 도움이 될 수 있는 피드백을 남겨주세요.
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('judge.video.list') }}" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 취소
                </a>
                <button type="submit" class="btn btn-admin btn-lg" id="submit-btn">
                    @if($assignment->evaluation)
                        <i class="bi bi-pencil"></i> 심사 결과 수정
                    @else
                        <i class="bi bi-check-circle"></i> 심사 완료
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    const ranges = document.querySelectorAll('input[type="range"]');
    const totalScoreElement = document.getElementById('total-score');
    const gradeBadge = document.getElementById('grade-badge');
    
    // 점수 입력과 슬라이더 동기화
    scoreInputs.forEach((input, index) => {
        const range = ranges[index];
        
        // 숫자 입력 시 슬라이더 업데이트
        input.addEventListener('input', function() {
            const value = Math.max(1, Math.min(100, parseInt(this.value) || 1));
            this.value = value;
            range.value = value;
            calculateTotal();
        });
        
        // 슬라이더 변경 시 숫자 입력 업데이트
        range.addEventListener('input', function() {
            input.value = this.value;
            calculateTotal();
        });
    });
    
    // 총점 계산
    function calculateTotal() {
        let total = 0;
        scoreInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        
        totalScoreElement.textContent = total;
        updateGrade(total);
    }
    
    // 등급 업데이트
    function updateGrade(total) {
        let grade, className;
        
        if (total >= 76) {
            grade = '우수 (A등급)';
            className = 'bg-success';
        } else if (total >= 51) {
            grade = '양호 (B등급)';
            className = 'bg-primary';
        } else if (total >= 26) {
            grade = '보통 (C등급)';
            className = 'bg-info';
        } else if (total >= 1) {
            grade = '미흡 (D등급)';
            className = 'bg-warning';
        } else {
            grade = '매우 미흡 (F등급)';
            className = 'bg-danger';
        }
        
        gradeBadge.textContent = grade;
        gradeBadge.className = `badge fs-6 ${className}`;
    }
    
    // 폼 제출 시 확인
    document.getElementById('evaluation-form').addEventListener('submit', function(e) {
        const scores = Array.from(scoreInputs).map(input => parseInt(input.value));
        const hasInvalidScore = scores.some(score => score < 1 || score > 100 || isNaN(score));
        
        if (hasInvalidScore) {
            e.preventDefault();
            alert('모든 점수는 1-100점 사이여야 합니다.');
            return;
        }
        
        const total = scores.reduce((sum, score) => sum + score, 0);
        const confirmMessage = `심사 결과를 저장하시겠습니까?\n\n총점: ${total}/40점\n` + 
                             `등급: ${gradeBadge.textContent}`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        } else {
            // 제출 버튼 비활성화
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 저장 중...';
        }
    });
    
    // 초기 총점 계산
    calculateTotal();
});
</script>
@endsection 