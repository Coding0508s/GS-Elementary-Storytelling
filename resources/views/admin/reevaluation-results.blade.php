@extends('admin.layout')

@section('title', '재평가 결과')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-arrow-repeat"></i> 재평가 결과</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.evaluation.reevaluation.results.excel', request()->query()) }}" 
           class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Excel 다운로드
        </a>
        <form action="{{ route('admin.evaluation.reevaluation.reset') }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('재평가 영상 배정 및 결과를 초기화하시겠습니까?\n\n※ 재평가 배정과 재평가 결과가 모두 삭제됩니다.');">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-arrow-counterclockwise"></i> 재평가 초기화
            </button>
        </form>
        <a href="{{ route('admin.dashboard') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드
        </a>
    </div>
</div>

<!-- 필터 및 검색 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> 필터 및 검색</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.evaluation.reevaluation.results') }}" method="GET" class="row">
            <div class="col-md-4 mb-3">
                <label for="search" class="form-label">검색</label>
                <input type="text" 
                       class="form-control" 
                       id="search" 
                       name="search" 
                       placeholder="학생명, 기관명 검색"
                       value="{{ request('search') }}">
            </div>
            
            <div class="col-md-2 mb-3">
                <label for="judge_id" class="form-label">심사위원</label>
                <select class="form-control" id="judge_id" name="judge_id">
                    <option value="">전체</option>
                    @foreach($judges as $judge)
                        <option value="{{ $judge->id }}" {{ request('judge_id') == $judge->id ? 'selected' : '' }}>
                            {{ $judge->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-2 mb-3">
                <label for="per_page" class="form-label">페이지당 항목 수</label>
                <select class="form-control" id="per_page" name="per_page">
                    <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20개</option>
                    <option value="50" {{ request('per_page') == 50 || !request('per_page') ? 'selected' : '' }}>50개</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100개</option>
                    <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200개</option>
                </select>
            </div>
            
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex w-100">
                    <button type="submit" class="btn btn-admin flex-fill">
                        <i class="bi bi-search"></i> 검색
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 통계 요약 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalReevaluations) }}</h3>
                <p class="card-text text-muted">전체 재평가</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
                <h3 class="text-info">{{ number_format($withOriginalCount) }}</h3>
                <p class="card-text text-muted">원본 평가 있음</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($scoreIncreasedCount) }}</h3>
                <p class="card-text text-muted">점수 상승</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-danger mb-2">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <h3 class="text-danger">{{ number_format($scoreDecreasedCount) }}</h3>
                <p class="card-text text-muted">점수 하락</p>
            </div>
        </div>
    </div>
</div>

<!-- 재평가 결과 목록 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-arrow-repeat"></i> 
            재평가 결과 목록 
            <span class="badge bg-light text-dark ms-2">{{ $paginated->total() }}개</span>
        </h5>
    </div>
    <div class="card-body">
        @if($paginated->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th width="80" class="text-center">순위</th>
                            <th>학생 정보</th>
                            <th>기관 정보</th>
                            <th class="text-center">합산 점수</th>
                            <th class="text-center">심사위원 수</th>
                            <th>심사위원별 평가</th>
                            <th width="120">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginated as $index => $videoData)
                        @php
                            $submission = $videoData->video_submission ?? null;
                            $judgeReevaluations = $videoData->judge_reevaluations ?? collect();
                            $totalScore = $videoData->total_reevaluation_score ?? 0;
                            $judgeCount = $videoData->judge_count ?? 0;
                            $rank = ($paginated->currentPage() - 1) * $paginated->perPage() + $index + 1;
                        @endphp
                        @if($submission)
                        <tr class="table-primary">
                            <td class="text-center align-middle">
                                <strong class="fs-5">{{ $rank }}</strong>
                            </td>
                            <td class="align-middle">
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small><br>
                                <small class="text-muted">
                                    {{ $submission->grade }}학년 ({{ $submission->age }}세)
                                </small>
                            </td>
                            
                            <td class="align-middle">
                                <strong>{{ $submission->institution_name }}</strong><br>
                                <small class="text-muted">{{ $submission->class_name }}</small><br>
                                <small class="text-muted">{{ $submission->region }}</small>
                            </td>
                            
                            <td class="text-center align-middle">
                                <span class="badge bg-primary fs-5 px-3 py-2">
                                    {{ number_format($totalScore) }}/{{ number_format($judgeCount * 70) }}
                                </span>
                                <br>
                                <small class="text-muted">
                                    평균: {{ $judgeCount > 0 ? number_format($totalScore / $judgeCount, 1) : 0 }}/70
                                    <br>
                                    <span class="text-info">(재평가 있으면 재평가 점수, 없으면 원본 평가 점수)</span>
                                </small>
                            </td>
                            
                            <td class="text-center align-middle">
                                <span class="badge bg-info fs-6">
                                    {{ $judgeCount }}명
                                </span>
                            </td>
                            
                            <td>
                                <div class="accordion" id="accordion{{ $submission->id }}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $submission->id }}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $submission->id }}" aria-expanded="false" aria-controls="collapse{{ $submission->id }}">
                                                <i class="bi bi-chevron-down me-2"></i>
                                                심사위원별 평가 상세 ({{ $judgeCount }}명)
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $submission->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $submission->id }}" data-bs-parent="#accordion{{ $submission->id }}">
                                            <div class="accordion-body p-0">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>심사위원</th>
                                                            <th class="text-center">원본 평가</th>
                                                            <th class="text-center">재평가 점수</th>
                                                            <th class="text-center">점수 차이</th>
                                                            <th class="text-center">재평가 일시</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($judgeReevaluations as $judgeData)
                                                        @php
                                                            $reevaluation = $judgeData->reevaluation ?? null;
                                                            $original = $judgeData->original_evaluation ?? null;
                                                            $judge = $judgeData->judge ?? null;
                                                            $scoreDiff = $judgeData->score_difference ?? null;
                                                        @endphp
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $judge->name ?? '알 수 없음' }}</strong>
                                                            </td>
                                                            <td class="text-center">
                                                                @if($original)
                                                                    <span class="badge bg-secondary">
                                                                        {{ $original->total_score }}/70
                                                                    </span>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        {{ $original->created_at->format('Y-m-d H:i') }}
                                                                    </small>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($reevaluation)
                                                                    <span class="badge bg-primary">
                                                                        {{ $reevaluation->total_score }}/70
                                                                    </span>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        {{ $reevaluation->created_at->format('Y-m-d H:i') }}
                                                                    </small>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($scoreDiff !== null)
                                                                    @if($scoreDiff > 0)
                                                                        <span class="badge bg-success">
                                                                            <i class="bi bi-arrow-up"></i> +{{ $scoreDiff }}
                                                                        </span>
                                                                    @elseif($scoreDiff < 0)
                                                                        <span class="badge bg-danger">
                                                                            <i class="bi bi-arrow-down"></i> {{ $scoreDiff }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-secondary">
                                                                            변화 없음
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($reevaluation)
                                                                    <small>{{ $reevaluation->created_at->format('Y-m-d') }}</small><br>
                                                                    <small class="text-muted">{{ $reevaluation->created_at->format('H:i') }}</small>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="align-middle">
                                <div class="d-grid gap-1">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            onclick="showVideoModal({{ $submission->id }}, '{{ $submission->student_name_korean }}', '{{ $submission->video_file_name }}')">
                                        <i class="bi bi-play-circle"></i> 영상 보기
                                    </button>
                                    <a href="{{ route('admin.video.download', $submission->id) }}" 
                                       class="btn btn-sm btn-outline-info"
                                       target="_blank">
                                        <i class="bi bi-download"></i> 다운로드
                                    </a>
                                    <a href="{{ route('admin.evaluation.show', $submission->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> 상세보기
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="mt-4">
                {{ $paginated->appends(request()->query())->links('custom.pagination') }}
            </div>
            
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <h4 class="text-muted mt-3">재평가된 영상이 없습니다</h4>
                <p class="text-muted">재평가가 완료되면 여기에 표시됩니다.</p>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
                </a>
            </div>
        @endif
    </div>
</div>

<!-- 영상 재생 모달 -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">
                    <i class="bi bi-camera-video"></i> 영상 재생
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
                        <h6 id="video-student-name" class="mb-1"></h6>
                        <small id="video-file-name" class="text-muted"></small>
                    </div>
                    <div class="ratio ratio-16x9 bg-dark rounded">
                        <video id="video-player" 
                               controls 
                               preload="metadata" 
                               class="w-100 h-100"
                               style="object-fit: contain;">
                            <source id="video-source" src="" type="">
                            영상을 재생할 수 없습니다. 브라우저가 이 형식을 지원하지 않습니다.
                        </video>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 영상 모달 표시 함수
function showVideoModal(videoId, studentName, fileName) {
    const modal = new bootstrap.Modal(document.getElementById('videoModal'));
    const modalElement = document.getElementById('videoModal');
    
    // 모달 내용 초기화
    document.getElementById('video-loading').classList.remove('d-none');
    document.getElementById('video-error').classList.add('d-none');
    document.getElementById('video-container').classList.add('d-none');
    document.getElementById('video-student-name').textContent = studentName;
    document.getElementById('video-file-name').textContent = fileName;
    
    // 기존 비디오 소스 제거
    const videoPlayer = document.getElementById('video-player');
    const videoSource = document.getElementById('video-source');
    videoSource.src = '';
    videoSource.type = '';
    videoPlayer.load();
    
    // 모달 표시
    modal.show();
    
    // 영상 URL 가져오기
    fetch(`/admin/video/${videoId}/stream-url`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
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
        if (data.success && data.video_url) {
            // 로딩 숨기기
            document.getElementById('video-loading').classList.add('d-none');
            
            // 영상 컨테이너 표시
            document.getElementById('video-container').classList.remove('d-none');
            
            // 비디오 소스 설정
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
                document.getElementById('video-error').classList.remove('d-none');
                document.getElementById('video-error-message').textContent = '영상을 재생할 수 없습니다. URL을 확인해주세요.';
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
    
    // 모달이 닫힐 때 비디오 정지
    modalElement.addEventListener('hidden.bs.modal', function() {
        videoPlayer.pause();
        videoPlayer.src = '';
        videoSource.src = '';
        videoSource.type = '';
        videoPlayer.load();
    }, { once: false });
}
</script>
@endsection

