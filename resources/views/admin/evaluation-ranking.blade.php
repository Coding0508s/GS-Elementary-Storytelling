@extends('admin.layout')

@section('title', '평가 순위')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-trophy"></i> 평가 순위</h1>
    <div class="d-flex gap-2">
        <button type="button" 
                class="btn btn-outline-info" 
                id="auto-refresh-btn"
                title="자동 새로고침 (5초 간격)">
            <i class="bi bi-arrow-clockwise"></i> 자동 새로고침
        </button>
        <a href="{{ route('admin.evaluation.ranking.excel', request()->query()) }}" 
           class="btn btn-success">
            <i class="bi bi-download"></i> Excel 다운로드
        </a>
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
        <form action="{{ route('admin.evaluation.ranking') }}" method="GET" class="row">
            <div class="col-md-4 mb-3">
                <label for="search" class="form-label">검색</label>
                <input type="text" 
                       class="form-control" 
                       id="search" 
                       name="search" 
                       placeholder="학생명, 기관명 검색"
                       value="{{ request('search') }}">
            </div>
            
            <div class="col-md-3 mb-3">
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
            
            <div class="col-md-3 mb-3">
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
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalCompleted) }}</h3>
                <p class="card-text text-muted">평가 완료 영상</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="text-success">{{ number_format($totalJudges) }}</h3>
                <p class="card-text text-muted">심사위원 수</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-list-ol"></i>
                </div>
                <h3 class="text-info">{{ number_format($paginated->total()) }}</h3>
                <p class="card-text text-muted">현재 표시</p>
            </div>
        </div>
    </div>
</div>

<!-- 순위 목록 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-trophy"></i> 
            평가 순위 
            <span class="badge bg-light text-dark ms-2">{{ $paginated->total() }}개</span>
            <small class="text-muted ms-2">(점수순, 동점 시 접수순)</small>
        </h5>
    </div>
    <div class="card-body">
        @if($paginated->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th width="80" class="text-center">순위</th>
                            <th width="100">접수번호</th>
                            <th>학생 정보</th>
                            <th>기관 정보</th>
                            <th>심사위원</th>
                            <th class="text-center">점수</th>
                            <th>접수일시</th>
                            <th width="120">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginated as $assignment)
                        @php
                            $submission = $assignment->videoSubmission;
                            $evaluation = $assignment->evaluation;
                        @endphp
                        @if($submission && $evaluation)
                        <tr>
                            <td class="text-center">
                                @if($assignment->rank <= 3)
                                    <span class="badge bg-{{ $assignment->rank == 1 ? 'warning' : ($assignment->rank == 2 ? 'secondary' : 'danger') }} fs-6">
                                        {{ $assignment->rank }}위
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark fs-6">
                                        {{ $assignment->rank }}위
                                    </span>
                                @endif
                            </td>
                            
                            <td>
                                <small class="text-muted">{{ $submission->receipt_number }}</small>
                            </td>
                            
                            <td>
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small><br>
                                <small class="text-muted">
                                    {{ $submission->grade }}학년 ({{ $submission->age }}세)
                                </small>
                            </td>
                            
                            <td>
                                <strong>{{ $submission->institution_name }}</strong><br>
                                <small class="text-muted">{{ $submission->class_name }}</small><br>
                                <small class="text-muted">{{ $submission->region }}</small>
                            </td>
                            
                            <td>
                                <strong>{{ $assignment->admin->name ?? '알 수 없음' }}</strong>
                            </td>
                            
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-primary fs-6 mb-1">
                                        {{ $evaluation->total_score }}/70
                                    </span>
                                    <small class="text-muted">
                                        <div class="row g-1 mt-1" style="font-size: 0.75rem;">
                                            <div class="col-6">발음 {{ $evaluation->pronunciation_score }}/10</div>
                                            <div class="col-6">어휘 {{ $evaluation->vocabulary_score }}/10</div>
                                            <div class="col-6">유창성 {{ $evaluation->fluency_score }}/10</div>
                                            <div class="col-6">자신감 {{ $evaluation->confidence_score }}/10</div>
                                            <div class="col-6">주제연결 {{ $evaluation->topic_connection_score }}/10</div>
                                            <div class="col-6">구성흐름 {{ $evaluation->structure_flow_score }}/10</div>
                                            <div class="col-12">창의성 {{ $evaluation->creativity_score }}/10</div>
                                        </div>
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <small>{{ $submission->created_at->format('Y-m-d') }}</small><br>
                                <small class="text-muted">{{ $submission->created_at->format('H:i') }}</small>
                            </td>
                            
                            <td>
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
                <h4 class="text-muted mt-3">평가 완료된 영상이 없습니다</h4>
                <p class="text-muted">영상이 평가되면 순위가 표시됩니다.</p>
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
// 자동 새로고침 관련 변수
let autoRefreshInterval = null;
let isAutoRefreshEnabled = false;

document.addEventListener('DOMContentLoaded', function() {
    // 검색 폼 자동 제출 (디바운스)
    let searchTimeout;
    const searchInput = document.getElementById('search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // 자동 검색은 선택사항이므로 주석 처리
                // this.form.submit();
            }, 500);
        });
    }
    
    // 자동 새로고침 버튼 이벤트
    const autoRefreshBtn = document.getElementById('auto-refresh-btn');
    if (autoRefreshBtn) {
        autoRefreshBtn.addEventListener('click', function() {
            toggleAutoRefresh();
        });
    }
});

// 자동 새로고침 토글 함수
function toggleAutoRefresh() {
    const btn = document.getElementById('auto-refresh-btn');
    
    if (isAutoRefreshEnabled) {
        // 자동 새로고침 중지
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        isAutoRefreshEnabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 자동 새로고침';
        btn.className = 'btn btn-outline-info';
        btn.title = '자동 새로고침 (5초 간격)';
        console.log('자동 새로고침 중지');
    } else {
        // 자동 새로고침 시작
        autoRefreshInterval = setInterval(function() {
            refreshRankingData();
        }, 5000); // 5초마다 새로고침
        isAutoRefreshEnabled = true;
        btn.innerHTML = '<i class="bi bi-pause-circle"></i> 새로고침 중지';
        btn.className = 'btn btn-info';
        btn.title = '자동 새로고침 중지';
        console.log('자동 새로고침 시작 (5초 간격)');
    }
}

// 순위 데이터 새로고침 함수
function refreshRankingData() {
    // 현재 URL과 쿼리 파라미터 유지
    const currentUrl = new URL(window.location.href);
    
    fetch(currentUrl.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => response.text())
    .then(html => {
        // 새로운 HTML에서 테이블과 통계 부분만 추출
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        // 테이블 tbody 업데이트
        const newTableBody = newDoc.querySelector('table tbody');
        const currentTableBody = document.querySelector('table tbody');
        
        if (newTableBody && currentTableBody) {
            // 현재 페이지의 데이터만 업데이트 (페이지네이션 유지)
            const currentPage = getCurrentPage();
            const newPage = getPageFromUrl(newDoc);
            
            // 같은 페이지일 때만 업데이트
            if (currentPage === newPage) {
                currentTableBody.innerHTML = newTableBody.innerHTML;
                console.log('순위 테이블 데이터 새로고침 완료');
            }
        }
        
        // 통계 카드 업데이트
        updateStatisticsCards(newDoc);
        
        // 총 개수 업데이트
        const newTotal = newDoc.querySelector('.badge.bg-light.text-dark');
        const currentTotal = document.querySelector('.badge.bg-light.text-dark');
        if (newTotal && currentTotal) {
            currentTotal.textContent = newTotal.textContent;
        }
    })
    .catch(error => {
        console.error('순위 데이터 새로고침 오류:', error);
    });
}

// 통계 카드 업데이트 함수
function updateStatisticsCards(newDoc) {
    const newStatsCards = newDoc.querySelectorAll('.stats-card h3');
    const currentStatsCards = document.querySelectorAll('.stats-card h3');
    
    if (newStatsCards.length === currentStatsCards.length) {
        newStatsCards.forEach((newCard, index) => {
            if (currentStatsCards[index]) {
                currentStatsCards[index].textContent = newCard.textContent;
            }
        });
    }
}

// 현재 페이지 번호 가져오기
function getCurrentPage() {
    const pagination = document.querySelector('.pagination');
    if (pagination) {
        const activePage = pagination.querySelector('.page-item.active');
        if (activePage) {
            const pageLink = activePage.querySelector('a, span');
            if (pageLink) {
                const pageText = pageLink.textContent.trim();
                return parseInt(pageText) || 1;
            }
        }
    }
    return 1;
}

// 새 문서에서 페이지 번호 가져오기
function getPageFromUrl(doc) {
    const pagination = doc.querySelector('.pagination');
    if (pagination) {
        const activePage = pagination.querySelector('.page-item.active');
        if (activePage) {
            const pageLink = activePage.querySelector('a, span');
            if (pageLink) {
                const pageText = pageLink.textContent.trim();
                return parseInt(pageText) || 1;
            }
        }
    }
    return 1;
}

// 페이지를 떠날 때 자동 새로고침 중지
window.addEventListener('beforeunload', function() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

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

