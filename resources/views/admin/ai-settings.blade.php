@extends('admin.layout')

@section('title', 'AI 설정')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-gear"></i> AI 설정</h1>
        <p class="text-muted mb-0">OpenAI API 및 AI 평가 관련 설정을 관리합니다.</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
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

<!-- AI 서비스 상태 -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 mb-2 {{ $apiKeySet ? 'text-success' : 'text-warning' }}">
                    <i class="bi {{ $apiKeySet ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>
                </div>
                <h5 class="card-title">OpenAI API</h5>
                <p class="card-text {{ $apiKeySet ? 'text-success' : 'text-warning' }}">
                    {{ $apiKeySet ? 'API 키 설정됨' : 'API 키 필요' }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 mb-2 {{ $ffmpegInstalled['installed'] ? 'text-success' : 'text-warning' }}">
                    <i class="bi {{ $ffmpegInstalled['installed'] ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>
                </div>
                <h5 class="card-title">FFmpeg</h5>
                <p class="card-text {{ $ffmpegInstalled['installed'] ? 'text-success' : 'text-warning' }}">
                    {{ $ffmpegInstalled['installed'] ? '설치됨' : '설치 필요' }}
                </p>
                @if($ffmpegInstalled['installed'])
                    <small class="text-muted">{{ $ffmpegInstalled['path'] }}</small>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-robot"></i>
                </div>
                <h5 class="card-title">AI 평가 통계</h5>
                <p class="card-text">
                    완료: {{ $aiStats['completed_evaluations'] }}<br>
                    실패: {{ $aiStats['failed_evaluations'] }}
                </p>
                <small class="text-muted">총 {{ $aiStats['total_evaluations'] }}개</small>
            </div>
        </div>
    </div>
</div>

<!-- OpenAI API 설정 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-key"></i> OpenAI API 키 설정</h5>
    </div>
    <div class="card-body">
        @if(!$apiKeySet)
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>OpenAI API 키가 설정되지 않았습니다.</strong><br>
                AI 채점 기능을 사용하려면 OpenAI API 키를 입력해주세요.
            </div>
        @endif
        
        <form action="{{ route('admin.ai.settings.update') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="openai_api_key" class="form-label">
                    <i class="bi bi-key"></i> OpenAI API 키
                </label>
                <input type="password" 
                       class="form-control" 
                       id="openai_api_key" 
                       name="openai_api_key" 
                       placeholder="{{ $apiKeySet ? '현재 API 키가 설정되어 있습니다' : 'sk-...' }}"
                       {{ !$apiKeySet ? 'required' : '' }}>
                <div class="form-text">
                    OpenAI 계정에서 발급받은 API 키를 입력하세요. 
                    <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API 키 발급받기</a>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> 
                    {{ $apiKeySet ? 'API 키 업데이트' : 'API 키 설정' }}
                </button>
                
                @if($apiKeySet)
                    <button type="button" class="btn btn-outline-info" id="test-api-btn">
                        <i class="bi bi-lightning"></i> API 연결 테스트
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- FFmpeg 설정 안내 -->
@if(!$ffmpegInstalled['installed'])
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-tools"></i> FFmpeg 설치 안내</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>FFmpeg가 설치되지 않았습니다.</strong><br>
            영상에서 오디오를 추출하여 더 정확한 AI 평가를 받으려면 FFmpeg 설치를 권장합니다.
        </div>
        
        <h6>설치 방법:</h6>
        <div class="row">
            <div class="col-md-6">
                <h7><strong>macOS (Homebrew):</strong></h7>
                <pre class="bg-light p-2 rounded"><code>brew install ffmpeg</code></pre>
            </div>
            <div class="col-md-6">
                <h7><strong>Ubuntu/Debian:</strong></h7>
                <pre class="bg-light p-2 rounded"><code>sudo apt update && sudo apt install ffmpeg</code></pre>
            </div>
        </div>
        
        <small class="text-muted">
            FFmpeg 없이도 AI 평가는 가능하지만, 영상 파일을 직접 Whisper API로 전송하므로 처리 시간이 길어질 수 있습니다.
        </small>
    </div>
</div>
@endif

<!-- AI 평가 정보 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> AI 평가 정보</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>평가 기준</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success"></i> <strong>발음 및 억양:</strong> 정확한 발음과 자연스러운 억양 (10점)</li>
                    <li><i class="bi bi-check text-success"></i> <strong>어휘 및 표현:</strong> 올바른 어휘 및 표현 사용 (10점)</li>
                    <li><i class="bi bi-check text-success"></i> <strong>유창성:</strong> 영어 구사 수준과 흐름 (10점)</li>
                </ul>
                <p class="text-muted">총 30점 만점으로 평가되며, 각 기준별로 상세한 피드백이 제공됩니다.</p>
            </div>
            
            <div class="col-md-6">
                <h6>기술 정보</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-cpu"></i> <strong>음성 인식:</strong> OpenAI Whisper-1</li>
                    <li><i class="bi bi-brain"></i> <strong>평가 모델:</strong> GPT-4</li>
                    <li><i class="bi bi-clock"></i> <strong>처리 시간:</strong> 영상 길이에 따라 1-15분</li>
                    <li><i class="bi bi-file-earmark"></i> <strong>지원 형식:</strong> MP4, MOV, AVI, WAV, MP3</li>
                    <li><i class="bi bi-hdd"></i> <strong>파일 크기:</strong> 최대 2GB (대용량 분할 처리)</li>
                    <li><i class="bi bi-scissors"></i> <strong>분할 처리:</strong> 20MB 초과 시 자동 청크 분할</li>
                </ul>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i>
            <strong>대용량 파일 처리:</strong> 
            <ul class="mb-0 mt-2">
                <li>20MB 초과 파일은 자동으로 5분 단위 청크로 분할되어 처리됩니다</li>
                <li>3시간 영상의 경우 약 36개 청크로 분할되어 10-15분 소요됩니다</li>
                <li>MP3 압축을 통해 파일 크기를 90% 이상 절약합니다</li>
                <li>일부 청크 처리 실패해도 나머지 부분은 정상 처리됩니다</li>
            </ul>
        </div>
        
        <div class="alert alert-light">
            <i class="bi bi-lightbulb"></i>
            <strong>팁:</strong> AI 평가는 참고용으로 사용하시고, 최종 평가는 심사위원의 판단을 우선하시기 바랍니다.
        </div>
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

pre code {
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testApiBtn = document.getElementById('test-api-btn');
    
    if (testApiBtn) {
        testApiBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 테스트 중...';
            
            // 간단한 테스트 요청
            fetch('{{ route("admin.ai.settings") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    alert('API 연결이 정상적으로 작동합니다!');
                } else {
                    alert('API 연결에 문제가 있습니다. 설정을 확인해주세요.');
                }
            })
            .catch(error => {
                alert('네트워크 오류가 발생했습니다.');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-lightning"></i> API 연결 테스트';
            });
        });
    }
});
</script>
@endsection
