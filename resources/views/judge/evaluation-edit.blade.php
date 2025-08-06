@extends('admin.layout')

@section('title', '심사 결과 수정')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-pencil-square"></i> 심사 결과 수정</h1>
    <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
</div>

<!-- 학생 정보 및 현재 심사 결과 -->
<div class="row mb-4">
    <!-- 학생 정보 -->
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> 학생 정보</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">학생명</label>
                        <p class="mb-0 fw-bold">{{ $submission->student_name_korean }} ({{ $submission->student_name_english }})</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">기관명</label>
                        <p class="mb-0">{{ $submission->institution_name }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">반</label>
                        <p class="mb-0">{{ $submission->class_name }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">학년/나이</label>
                        <p class="mb-0">{{ $submission->grade }} ({{ $submission->age }}세)</p>
                    </div>
                    @if($submission->unit_topic)
                    <div class="col-12">
                        <label class="form-label text-muted small">Unit 주제</label>
                        <p class="mb-0 fw-bold text-primary">{{ $submission->unit_topic }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 현재 심사 결과 -->
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> 현재 심사 결과</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">발음 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->pronunciation_score }}/100</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">어휘 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->vocabulary_score }}/100</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">유창성 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->fluency_score }}/100</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">자신감 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->confidence_score }}/100</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">총점</label>
                        <p class="mb-0 fw-bold text-success fs-4">{{ $assignment->evaluation->total_score }}/100</p>
                    </div>
                    @if($assignment->evaluation->comments)
                    <div class="col-12 mt-3">
                        <label class="form-label text-muted small">현재 코멘트</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ $assignment->evaluation->comments }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 수정 폼 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> 심사 결과 수정</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('judge.evaluation.update', $assignment->id) }}" method="POST" id="evaluation-form">
            @csrf
            @method('PUT')
            
            <!-- 평가 기준 -->
            <div class="row mb-4">
                @php
                    $criteria = [
                        'pronunciation_score' => ['title' => '정확한 발음과 자연스러운 억양, 전달력', 'icon' => 'bi-mic'],
                        'vocabulary_score' => ['title' => '올바른 어휘 및 표현 사용', 'icon' => 'bi-book'],
                        'fluency_score' => ['title' => '유창성 수준', 'icon' => 'bi-chat-dots'],
                        'confidence_score' => ['title' => '자신감, 긍정적이고 밝은 태도', 'icon' => 'bi-emoji-smile']
                    ];
                @endphp
                
                @foreach($criteria as $field => $info)
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="{{ $info['icon'] }}"></i> {{ $info['title'] }}
                            </h6>
                            
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label">점수 (1-100점)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" 
                                           class="form-range flex-grow-1" 
                                           id="{{ $field }}_range"
                                           min="1" 
                                           max="100" 
                                           step="1"
                                           value="{{ old($field, $assignment->evaluation->$field) }}">
                                    <input type="number" 
                                           class="form-control score-input" 
                                           id="{{ $field }}"
                                           name="{{ $field }}"
                                           min="1" 
                                           max="100" 
                                           value="{{ old($field, $assignment->evaluation->$field) }}"
                                           required>
                                </div>
                            </div>
                            
                            <!-- 점수 가이드 -->
                            <div class="text-muted small">
                                <strong>점수 가이드:</strong><br>
                                1-25: 미흡 | 26-50: 보통 | 51-75: 양호 | 76-100: 우수
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- 총점 표시 -->
            <div class="card mb-4">
                <div class="card-body text-center bg-primary bg-opacity-10">
                    <h5 class="card-title">수정된 총점</h5>
                    <div class="display-6 fw-bold text-primary">
                        <span id="total-score">{{ $assignment->evaluation->total_score }}</span> / 100점
                    </div>
                    <div class="mt-2">
                        <span id="grade-badge" class="badge fs-6">등급 계산 중...</span>
                    </div>
                </div>
            </div>
            
            <!-- 심사 코멘트 -->
            <div class="mb-4">
                <label for="comments" class="form-label">
                    <i class="bi bi-chat-text"></i> 심사 코멘트 수정
                </label>
                <textarea class="form-control" 
                          id="comments" 
                          name="comments" 
                          rows="4"
                          placeholder="학생의 발표에 대한 구체적인 피드백을 입력해주세요...">{{ old('comments', $assignment->evaluation->comments) }}</textarea>
                <div class="form-text">
                    학생과 학부모에게 도움이 될 수 있는 피드백을 남겨주세요.
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="d-flex gap-3 justify-content-end">
                <a href="{{ route('judge.video.list') }}" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 취소
                </a>
                <button type="submit" class="btn btn-admin">
                    <i class="bi bi-check-circle"></i> 수정 완료
                </button>
            </div>
        </form>
    </div>
</div>

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
            className = 'bg-success text-white';
        } else if (total >= 51) {
            grade = '양호 (B등급)';
            className = 'bg-primary text-white';
        } else if (total >= 26) {
            grade = '보통 (C등급)';
            className = 'bg-warning text-dark';
        } else if (total >= 1) {
            grade = '미흡 (D등급)';
            className = 'bg-danger text-white';
        } else {
            grade = '매우 미흡 (F등급)';
            className = 'bg-secondary text-white';
        }
        
        gradeBadge.textContent = grade;
        gradeBadge.className = `badge ${className} fs-6`;
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
        
        if (!confirm('심사 결과를 수정하시겠습니까?')) {
            e.preventDefault();
            return;
        }
    });
    
    // 초기 총점 계산
    calculateTotal();
});
</script>
@endsection 