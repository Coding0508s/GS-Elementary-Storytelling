@extends('admin.layout')

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
                
                <!-- 영상 플레이어 및 다운로드 -->
                <div class="mb-3">
                    <label class="form-label text-muted">영상 파일</label>
                    <div class="border rounded overflow-hidden">
                        <!-- 영상 플레이어 -->
                        <div id="video-container" class="position-relative" style="background: #000; min-height: 300px;">
                            <div id="video-loading" class="d-flex align-items-center justify-content-center h-100 text-white">
                                <div class="text-center">
                                    <div class="spinner-border mb-3" role="status">
                                        <span class="visually-hidden">로딩중...</span>
                                    </div>
                                    <p>영상을 로딩 중입니다...</p>
                                </div>
                            </div>
                            <video id="video-player" class="w-100 h-100" controls style="display: none; max-height: 400px;">
                                <source src="" type="video/mp4">
                                <p>브라우저가 영상 재생을 지원하지 않습니다.</p>
                            </video>
                        </div>
                        
                        <!-- 영상 컨트롤 패널 -->
                        <div class="p-3 bg-light border-top">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1 fw-bold">{{ $submission->video_file_name }}</h6>
                                    <small class="text-muted">
                                        {{ strtoupper($submission->video_file_type) }} · 
                                        {{ $submission->getFormattedFileSizeAttribute() }} · 
                                        업로드: {{ $submission->created_at->format('m/d H:i') }}
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="load-video-btn">
                                            <i class="bi bi-play-fill"></i> 재생
                                        </button>
                                        <a href="{{ route('judge.video.download', $assignment->id) }}" 
                                           class="btn btn-outline-success btn-sm"
                                           target="_blank">
                                            <i class="bi bi-download"></i> 다운로드
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 영상 상태 표시 -->
                            <div id="video-status" class="mt-2" style="display: none;">
                                <div class="alert alert-info alert-sm mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    <span id="status-text">영상이 준비되었습니다.</span>
                                </div>
                            </div>
                            
                            <!-- 에러 표시 -->
                            <div id="video-error" class="mt-2" style="display: none;">
                                <div class="alert alert-danger alert-sm mb-0">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="error-text">영상을 로드할 수 없습니다.</span>
                                </div>
                            </div>
                        </div>
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
                                    점수 (0-10점)
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" 
                                           class="form-range flex-grow-1" 
                                           id="{{ $field }}_range"
                                           min="0" 
                                           max="10" 
                                           step="1"
                                           value="{{ old($field, $assignment->evaluation->$field ?? 5) }}">
                                    <input type="number" 
                                           class="form-control score-input" 
                                           id="{{ $field }}"
                                           name="{{ $field }}"
                                           min="0" 
                                           max="10" 
                                           value="{{ old($field, $assignment->evaluation->$field ?? '') }}"
                                           required>
                                </div>
                            </div>
                            
                            <!-- 점수 가이드 -->
                            <div class="score-guide">
                                <small class="text-muted">
                                    <strong>점수 가이드:</strong><br>
                                    0-2: 매우 미흡 | 3-4: 미흡 | 5-6: 보통 | 7-8: 양호 | 9-10: 우수
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
    // 영상 관련 요소들
    const loadVideoBtn = document.getElementById('load-video-btn');
    const videoPlayer = document.getElementById('video-player');
    const videoLoading = document.getElementById('video-loading');
    const videoStatus = document.getElementById('video-status');
    const videoError = document.getElementById('video-error');
    const statusText = document.getElementById('status-text');
    const errorText = document.getElementById('error-text');
    
    // 심사 관련 요소들
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    const ranges = document.querySelectorAll('input[type="range"]');
    const totalScoreElement = document.getElementById('total-score');
    const gradeBadge = document.getElementById('grade-badge');
    
    // 영상 로드 버튼 클릭
    loadVideoBtn.addEventListener('click', function() {
        loadVideo();
    });
    
    // 영상 로드 함수
    function loadVideo() {
        loadVideoBtn.disabled = true;
        loadVideoBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 로딩중...';
        
        videoLoading.style.display = 'flex';
        videoStatus.style.display = 'none';
        videoError.style.display = 'none';
        
        // S3 스트리밍 URL 요청
        fetch('{{ route("judge.video.stream-url", $assignment->id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 영상 URL 설정
                    const source = videoPlayer.querySelector('source');
                    source.src = data.url;
                    videoPlayer.load();
                    
                    // 영상 로드 완료 시
                    videoPlayer.addEventListener('loadeddata', function() {
                        videoLoading.style.display = 'none';
                        videoPlayer.style.display = 'block';
                        
                        videoStatus.style.display = 'block';
                        statusText.textContent = `영상이 로드되었습니다. (${data.size})`;
                        
                        loadVideoBtn.disabled = false;
                        loadVideoBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 새로고침';
                    });
                    
                    // 영상 로드 에러 시
                    videoPlayer.addEventListener('error', function() {
                        showVideoError('영상을 재생할 수 없습니다.');
                    });
                    
                } else {
                    showVideoError(data.error || '영상 URL을 가져올 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showVideoError('네트워크 오류가 발생했습니다.');
            });
    }
    
    // 영상 에러 표시
    function showVideoError(message) {
        videoLoading.style.display = 'none';
        videoError.style.display = 'block';
        errorText.textContent = message;
        
        loadVideoBtn.disabled = false;
        loadVideoBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 다시 시도';
    }
    
    // 점수 입력과 슬라이더 동기화
    scoreInputs.forEach((input, index) => {
        const range = ranges[index];
        
        // 숫자 입력 시 슬라이더 업데이트
        input.addEventListener('input', function() {
            const value = Math.max(0, Math.min(10, parseInt(this.value) || 0));
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
    
    // 총점 계산 및 환산 점수 계산
    function calculateTotal() {
        let total = 0;
        const scores = {
            pronunciation: 0,
            vocabulary: 0,
            fluency: 0,
            confidence: 0
        };
        
        scoreInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
            
            // 각 항목별 점수 저장
            const fieldName = input.name.replace('_score', '');
            scores[fieldName] = value;
        });
        
        totalScoreElement.textContent = total;
        updateGrade(total);
        calculateConvertedScores(scores, total);
    }
    
    // 환산 점수 계산
    function calculateConvertedScores(scores, total) {
        if (total === 0) {
            // 총점이 0이면 모든 환산 점수도 0
            document.getElementById('pronunciation-converted').textContent = '0.0';
            document.getElementById('vocabulary-converted').textContent = '0.0';
            document.getElementById('fluency-converted').textContent = '0.0';
            document.getElementById('confidence-converted').textContent = '0.0';
            document.getElementById('total-converted').textContent = '0.0';
            return;
        }
        
        // 각 항목의 환산 점수 계산
        const convertedScores = {
            pronunciation: Math.round((scores.pronunciation / total) * 100 * 10) / 10,
            vocabulary: Math.round((scores.vocabulary / total) * 100 * 10) / 10,
            fluency: Math.round((scores.fluency / total) * 100 * 10) / 10,
            confidence: Math.round((scores.confidence / total) * 100 * 10) / 10
        };
        
        // 반올림 오차 보정
        let convertedTotal = convertedScores.pronunciation + convertedScores.vocabulary + 
                           convertedScores.fluency + convertedScores.confidence;
        
        if (Math.abs(convertedTotal - 100.0) > 0.1) {
            // 가장 큰 점수에 오차 보정
            const maxField = Object.keys(scores).reduce((a, b) => scores[a] > scores[b] ? a : b);
            const difference = 100.0 - convertedTotal;
            convertedScores[maxField] = Math.round((convertedScores[maxField] + difference) * 10) / 10;
        }
        
        // 화면에 표시
        document.getElementById('pronunciation-converted').textContent = convertedScores.pronunciation.toFixed(1);
        document.getElementById('vocabulary-converted').textContent = convertedScores.vocabulary.toFixed(1);
        document.getElementById('fluency-converted').textContent = convertedScores.fluency.toFixed(1);
        document.getElementById('confidence-converted').textContent = convertedScores.confidence.toFixed(1);
        document.getElementById('total-converted').textContent = '100.0';
    }
    
    // 등급 업데이트 (0~40점 기준)
    function updateGrade(total) {
        let grade, className;
        
        if (total >= 32) {
            grade = '우수 (A등급)';
            className = 'bg-success';
        } else if (total >= 24) {
            grade = '양호 (B등급)';
            className = 'bg-primary';
        } else if (total >= 16) {
            grade = '보통 (C등급)';
            className = 'bg-info';
        } else if (total >= 8) {
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
        const hasInvalidScore = scores.some(score => score < 0 || score > 10 || isNaN(score));
        
        if (hasInvalidScore) {
            e.preventDefault();
            alert('모든 점수는 0-10점 사이여야 합니다.');
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