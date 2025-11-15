@extends('admin.layout')

@section('title', 'AI 채점 결과 상세')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-robot"></i> AI 채점 결과 상세</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.ai.evaluation.list') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 목록으로
        </a>
    </div>
</div>

<!-- 영상 정보 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-fill"></i> 영상 정보</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">접수번호</th>
                        <td><code>{{ $aiEvaluation->videoSubmission->receipt_number }}</code></td>
                    </tr>
                    <tr>
                        <th>학생명(한글)</th>
                        <td>{{ $aiEvaluation->videoSubmission->student_name_korean }}</td>
                    </tr>
                    <tr>
                        <th>학생명(영문)</th>
                        <td>{{ $aiEvaluation->videoSubmission->student_name_english }}</td>
                    </tr>
                    <tr>
                        <th>기관명</th>
                        <td>{{ $aiEvaluation->videoSubmission->institution_name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">학년</th>
                        <td>{{ $aiEvaluation->videoSubmission->grade }}</td>
                    </tr>
                    <tr>
                        <th>나이</th>
                        <td>{{ $aiEvaluation->videoSubmission->age }}세</td>
                    </tr>
                    <tr>
                        <th>발표 주제</th>
                        <td>{{ $aiEvaluation->videoSubmission->unit_topic }}</td>
                    </tr>
                    <tr>
                        <th>영상 크기</th>
                        <td>{{ $aiEvaluation->videoSubmission->getFormattedFileSizeAttribute() }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AI 평가 상태 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> AI 평가 상태</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">처리 상태</th>
                        <td>
                            @if($aiEvaluation->processing_status === 'completed')
                                <span class="badge bg-success fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @elseif($aiEvaluation->processing_status === 'processing')
                                <span class="badge bg-warning fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @elseif($aiEvaluation->processing_status === 'failed')
                                <span class="badge bg-danger fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @else
                                <span class="badge bg-secondary fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>평가자</th>
                        <td>{{ $aiEvaluation->admin->name ?? '시스템' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">시작 시간</th>
                        <td>{{ $aiEvaluation->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>완료 시간</th>
                        <td>
                            @if($aiEvaluation->processed_at)
                                {{ $aiEvaluation->processed_at->format('Y-m-d H:i:s') }}
                            @else
                                <span class="text-muted">미완료</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($aiEvaluation->error_message)
        <div class="alert alert-danger mt-3">
            <h6><i class="bi bi-exclamation-triangle"></i> 오류 메시지</h6>
            <p class="mb-0">{{ $aiEvaluation->error_message }}</p>
        </div>
        @endif
    </div>
</div>

@if($aiEvaluation->isCompleted())
<!-- AI 평가 결과 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-award"></i> AI 평가 점수</h5>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-primary">{{ $aiEvaluation->pronunciation_score }}<small>/10</small></h3>
                        <p class="mb-0">발음/억양/전달력</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-success">{{ $aiEvaluation->vocabulary_score }}<small>/10</small></h3>
                        <p class="mb-0">어휘/표현</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-info">{{ $aiEvaluation->fluency_score }}<small>/10</small></h3>
                        <p class="mb-0">유창성</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3>{{ $aiEvaluation->total_score }}<small>/30</small></h3>
                        <p class="mb-0">총점</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 음성 인식 텍스트 -->
@if($aiEvaluation->transcription)
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-text"></i> 음성 인식 텍스트</h5>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded">
            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $aiEvaluation->transcription }}</pre>
        </div>
    </div>
</div>
@endif

<!-- AI 심사평 -->
@if($aiEvaluation->ai_feedback)
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-chat-dots"></i> AI 심사평 (영어)</h5>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded">
            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $aiEvaluation->ai_feedback }}</pre>
        </div>
    </div>
</div>
@endif

@endif

<!-- 영상 재생 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-camera-video"></i> 영상 재생</h5>
    </div>
    <div class="card-body">
        <div id="video-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <p class="mt-3 text-muted">영상을 불러오는 중...</p>
        </div>
        <div id="video-error" class="alert alert-danger d-none" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <span id="video-error-message"></span>
        </div>
        <div id="video-container" class="d-none">
            <div class="mb-3">
                <h6 class="mb-1">{{ $aiEvaluation->videoSubmission->student_name_korean }}</h6>
                <small class="text-muted">{{ $aiEvaluation->videoSubmission->video_file_name }}</small>
            </div>
            <div class="ratio ratio-16x9 bg-dark rounded">
                <video id="video-player" 
                       controls 
                       preload="metadata" 
                       class="w-100 h-100"
                       style="object-fit: contain;"
                       crossorigin="anonymous">
                    <source id="video-source" src="" type="">
                    영상을 재생할 수 없습니다. 브라우저가 이 형식을 지원하지 않습니다.
                </video>
            </div>
        </div>
    </div>
</div>

<!-- 평가 기준 안내 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> AI 평가 기준</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach(\App\Models\AiEvaluation::getCriteriaLabels() as $key => $label)
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-primary">{{ $label }}</h6>
                    <small class="text-muted">0~10점</small>
                    <div class="mt-2">
                        @foreach(\App\Models\AiEvaluation::getScoreGuide() as $range => $desc)
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary">{{ $range }}점</span>
                                <small>{{ $desc }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoId = {{ $aiEvaluation->videoSubmission->id }};
    
    // 영상 URL 가져오기
    fetch(`/admin/video/${videoId}/stream-url`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        console.log('API 응답 상태:', response.status);
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.error || '영상 URL을 가져올 수 없습니다.');
            }).catch(() => {
                throw new Error(`서버 오류 (${response.status}): 영상 URL을 가져올 수 없습니다.`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('영상 데이터:', data);
        if (data.success && data.video_url) {
            // 로딩 숨기기
            document.getElementById('video-loading').classList.add('d-none');
            
            // 영상 컨테이너 표시
            document.getElementById('video-container').classList.remove('d-none');
            
            // 비디오 소스 설정
            const videoPlayer = document.getElementById('video-player');
            const videoSource = document.getElementById('video-source');
            const videoType = data.video_type || 'mp4';
            
            videoSource.src = data.video_url;
            videoSource.type = `video/${videoType}`;
            
            // 비디오 플레이어에 직접 src 설정 (fallback)
            videoPlayer.src = data.video_url;
            
            // 비디오 로드 시도
            videoPlayer.load();
            
            // 비디오 로드 오류 처리
            videoPlayer.addEventListener('error', function(e) {
                console.error('비디오 로드 오류:', e);
                console.error('비디오 URL:', data.video_url);
                console.error('비디오 타입:', videoType);
                document.getElementById('video-error').classList.remove('d-none');
                document.getElementById('video-error-message').textContent = '영상을 재생할 수 없습니다. URL을 확인해주세요.';
            }, { once: true });
            
            // 비디오 로드 성공 확인
            videoPlayer.addEventListener('loadedmetadata', function() {
                console.log('비디오 메타데이터 로드 완료');
            }, { once: true });
        } else {
            throw new Error(data.error || '영상 URL을 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('영상 로드 오류:', error);
        document.getElementById('video-loading').classList.add('d-none');
        document.getElementById('video-error').classList.remove('d-none');
        document.getElementById('video-error-message').textContent = error.message || '영상을 불러오는 중 오류가 발생했습니다.';
    });
});
</script>
@endpush

@endsection
