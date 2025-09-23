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
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->pronunciation_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">어휘 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->vocabulary_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">유창성 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->fluency_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">자신감 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->confidence_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">주제연결성 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->topic_connection_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">구성·흐름 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->structure_flow_score }}/10</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">창의성 점수</label>
                        <p class="mb-0 fw-bold text-primary">{{ $assignment->evaluation->creativity_score }}/10</p>
                    </div>
                    <div class="col-12 text-center">
                        <label class="form-label fw-bold fs-5">총점</label>
                        <p class="mb-0 fw-bold text-success fs-3">{{ $assignment->evaluation->total_score }}/70</p>
                    </div>
                    @if($assignment->evaluation->comments)
                    <div class="col-12 mt-3">
                        <label class="form-label text-muted small">심사평</label>
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

<!-- 영상 플레이어 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-play-circle"></i> 영상 다시보기</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted">파일명</label>
                <p>{{ $submission->video_file_name }}</p>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted">파일 형식</label>
                <p>{{ strtoupper($submission->video_file_type) }}</p>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted">파일 크기</label>
                <p>{{ $submission->getFormattedFileSizeAttribute() }}</p>
            </div>
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
                            <p class="mb-0">재생 버튼을 클릭해주세요.</p>
                        </div>
                    </div>
                    <video id="video-player" 
                           controls 
                           preload="metadata" 
                           class="w-100" 
                           style="display: none; max-height: 500px;">
                        영상을 재생할 수 없습니다.
                    </video>
                </div>
                
                <!-- 영상 제어 버튼 -->
                <div class="p-3 bg-light border-top">
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" 
                                class="btn btn-primary" 
                                id="play-video-btn"
                                onclick="loadAndPlayVideo()">
                            <i class="bi bi-play-fill"></i> 재생
                        </button>
                        <a href="{{ route('judge.video.download', $assignment->id) }}" 
                           class="btn btn-outline-secondary" 
                           target="_blank">
                            <i class="bi bi-download"></i> 다운로드
                        </a>
                    </div>
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
                        'confidence_score' => ['title' => '자신감, 긍정적이고 밝은 태도', 'icon' => 'bi-emoji-smile'],
                        'topic_connection_score' => ['title' => '주제와 발표 내용과의 연결성', 'icon' => 'bi-link-45deg'],
                        'structure_flow_score' => ['title' => '자연스러운 구성과 흐름', 'icon' => 'bi-arrow-down-up'],
                        'creativity_score' => ['title' => '창의적 내용', 'icon' => 'bi-lightbulb']
                    ];
                @endphp
                
                @foreach($criteria as $field => $info)
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="{{ $info['icon'] }}"></i> {{ $info['title'] }}
                            </h6>
                            
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label">점수 (0-10점)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="range-wrap flex-grow-1">
                                        <input type="range" 
                                               class="form-range w-100" 
                                               id="{{ $field }}_range"
                                               min="0" 
                                               max="10" 
                                               step="1"
                                               value="{{ old($field, $assignment->evaluation->$field ?? 0) }}">
                                        <div class="range-ticks mt-1" style="display: flex; justify-content: space-between; padding: 0 8px; margin-top: 8px;">
                                            @for($i = 0; $i <= 10; $i++)
                                                <span class="tick" style="font-size: 11px; color: #6c757d; font-weight: 500; text-align: center;">{{ $i }}</span>
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="number" 
                                           class="form-control score-input" 
                                           id="{{ $field }}"
                                           name="{{ $field }}"
                                           min="0" 
                                           max="10" 
                                           value="{{ old($field, $assignment->evaluation->$field) }}"
                                           style="width: 80px;"
                                           required>
                                </div>
                            </div>
                            
                            <!-- 점수 가이드 -->
                            <div class="text-muted small">
                                <strong>점수 가이드:</strong><br>
                                각 항목별 0-10점으로 평가해주세요
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
                            <span id="total-score">{{ $assignment->evaluation->total_score }}</span> / 70점
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
    
    // 총점 계산
    function calculateTotal() {
        let total = 0;
        scoreInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        
        totalScoreElement.textContent = total;
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
        
        if (!confirm('심사 결과를 수정하시겠습니까?')) {
            e.preventDefault();
            return;
        }
    });
    
    // 초기 총점 계산
    calculateTotal();
});

// 영상 로딩 및 재생
function loadAndPlayVideo() {
    const videoContainer = document.getElementById('video-container');
    const videoLoading = document.getElementById('video-loading');
    const videoPlayer = document.getElementById('video-player');
    const playBtn = document.getElementById('play-video-btn');
    
    // 재생 버튼 클릭과 동시에 로딩 화면 즉시 숨기기
    if (videoLoading) {
        videoLoading.style.display = 'none';
        // DOM에서 완전히 제거
        try {
            if (videoLoading.parentNode) {
                videoLoading.parentNode.removeChild(videoLoading);
            }
        } catch (e) {
            console.log('Loading element already removed');
        }
    }
    
    // 비디오 플레이어 표시
    if (videoPlayer) {
        videoPlayer.style.display = 'block';
    }
    
    // 버튼 상태 변경
    playBtn.disabled = true;
    playBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>로딩중...';
    
    // 영상 URL 가져오기
    fetch('{{ route("judge.video.stream", $assignment->id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                videoPlayer.src = data.url;
                videoPlayer.load();
                
                // 로딩 완료 후 재생
                videoPlayer.addEventListener('loadeddata', function() {
                    playBtn.disabled = false;
                    playBtn.innerHTML = '<i class="bi bi-play-fill"></i> 재생됨';
                    videoPlayer.play();
                });
                
                // 에러 처리
                videoPlayer.addEventListener('error', function() {
                    // 에러 메시지를 비디오 컨테이너에 직접 표시
                    videoPlayer.style.display = 'none';
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'd-flex align-items-center justify-content-center h-100 text-white';
                    errorDiv.innerHTML = '<div class="text-center"><i class="bi bi-exclamation-triangle display-4 mb-3"></i><p>영상을 로드할 수 없습니다.</p></div>';
                    videoContainer.appendChild(errorDiv);
                    
                    playBtn.disabled = false;
                    playBtn.innerHTML = '<i class="bi bi-play-fill"></i> 재생';
                });
            } else {
                alert('영상 URL을 가져올 수 없습니다.');
                playBtn.disabled = false;
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i> 재생';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('영상을 로드하는 중 오류가 발생했습니다.');
            playBtn.disabled = false;
            playBtn.innerHTML = '<i class="bi bi-play-fill"></i> 재생';
        });
}
</script>
@endsection