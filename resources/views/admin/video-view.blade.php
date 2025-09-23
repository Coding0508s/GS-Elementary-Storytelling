@extends('admin.layout')

@section('title', '영상 보기')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-play-circle"></i> 영상 보기</h1>
        <p class="text-muted mb-0">{{ $submission->student_name_korean }} ({{ $submission->student_name_english }})</p>
    </div>
    <a href="{{ route('admin.ai.evaluation.list') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> AI 채점 결과로 돌아가기
    </a>
</div>

<!-- 학생 정보 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-circle"></i> 학생 정보</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted small">학생명</label>
                <p class="mb-0 fw-bold">{{ $submission->student_name_korean }} ({{ $submission->student_name_english }})</p>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted small">기관명</label>
                <p class="mb-0">{{ $submission->institution_name }}</p>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted small">학년</label>
                <p class="mb-0">{{ $submission->grade }}</p>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted small">나이</label>
                <p class="mb-0">{{ $submission->age }}세</p>
            </div>
            @if($submission->unit_topic)
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small">Unit 주제</label>
                <p class="mb-0 fw-bold text-primary">{{ $submission->unit_topic }}</p>
            </div>
            @endif
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small">업로드 일시</label>
                <p class="mb-0">{{ $submission->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- 영상 플레이어 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-camera-video"></i> 영상 플레이어</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label text-muted">파일명</label>
                <p>{{ $submission->video_file_name }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">파일 형식</label>
                <p>{{ strtoupper($submission->video_file_type) }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">파일 크기</label>
                <p>{{ $submission->getFormattedFileSizeAttribute() }}</p>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label text-muted">영상 파일</label>
            <div class="border rounded overflow-hidden">
                <!-- 영상 플레이어 -->
                <div id="video-container" class="position-relative" style="background: #000; min-height: 400px;">
                    @if($videoUrl)
                        <video id="video-player" 
                               controls 
                               preload="metadata" 
                               class="w-100" 
                               style="max-height: 600px;">
                            <source src="{{ $videoUrl }}" type="video/{{ $submission->video_file_type }}">
                            영상을 재생할 수 없습니다. 브라우저가 이 형식을 지원하지 않습니다.
                        </video>
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-white">
                            <div class="text-center">
                                <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                                <p class="mb-0">영상 URL을 생성할 수 없습니다.</p>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- 영상 제어 버튼 -->
                <div class="p-3 bg-light border-top text-center">
                    @php
                        $assignmentId = $submission->assignments->first()->id ?? null;
                    @endphp
                    
                    @if($assignmentId)
                        <a href="{{ route('judge.video.download', $assignmentId) }}" 
                           class="btn btn-outline-primary me-2" 
                           target="_blank">
                            <i class="bi bi-download"></i> 영상 다운로드
                        </a>
                    @else
                        <!-- 배정이 없는 경우 대체 다운로드 링크 -->
                        <a href="{{ asset('storage/' . $submission->video_file_path) }}" 
                           class="btn btn-outline-primary me-2" 
                           target="_blank"
                           download="{{ $submission->video_file_name }}">
                            <i class="bi bi-download"></i> 영상 다운로드
                        </a>
                    @endif
                    
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="window.close()">
                        <i class="bi bi-x-circle"></i> 창 닫기
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoPlayer = document.getElementById('video-player');
    
    if (videoPlayer) {
        // 영상 로드 에러 처리
        videoPlayer.addEventListener('error', function() {
            const container = document.getElementById('video-container');
            container.innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100 text-white">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                        <p class="mb-0">영상을 재생할 수 없습니다.</p>
                        <small>파일이 손상되었거나 지원되지 않는 형식일 수 있습니다.</small>
                    </div>
                </div>
            `;
        });
    }
});
</script>
@endsection
