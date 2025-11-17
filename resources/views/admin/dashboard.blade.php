@extends('admin.layout')

@section('title', '대시보드')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-speedometer2"></i> 관리자 대시보드</h1>
    <div class="d-flex align-items-center gap-3">
        <!-- 대회 상태 토글 -->
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted">대회 페이지:</span>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="contestToggle" 
                       {{ $contestActive ? 'checked' : '' }} 
                       style="transform: scale(1.2);">
                <label class="form-check-label fw-bold" for="contestToggle" id="contestStatusLabel">
                    {{ $contestActive ? '활성화' : '비활성화' }}
                </label>
            </div>
        </div>
        <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
    </div>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalSubmissions) }}</h3>
                <p class="card-text text-muted">총 접수 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($evaluatedSubmissions) }}</h3>
                <p class="card-text text-muted">심사 완료</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($pendingSubmissions) }}</h3>
                <p class="card-text text-muted">심사 대기</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-person-check"></i>
                </div>
                <h3 class="text-info">{{ number_format($assignedSubmissions) }}</h3>
                <p class="card-text text-muted">배정된 영상</p>
            </div>
        </div>
    </div>
</div>

<!-- 대회 활성화 상태 -->
<div class="card admin-card mb-4">
    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0 text-white">
            <i class="bi bi-trophy"></i> 대회 관리
        </h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        @if($contestActive)
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                        @else
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 2rem;"></i>
                        @endif
                    </div>
                    <div>
                        <h4 class="mb-1">
                            @if($contestActive)
                                <span class="text-success">대회 활성화됨</span>
                            @else
                                <span class="text-danger">대회 비활성화됨</span>
                            @endif
                        </h4>
                        <p class="text-muted mb-0">
                            @if($contestActive)
                                현재 사용자들이 대회에 참여할 수 있습니다.
                            @else
                                현재 대회가 비활성화되어 사용자 접근이 제한됩니다.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-grid">
                    <button id="contest-toggle-btn" 
                            class="btn {{ $contestActive ? 'btn-danger' : 'btn-success' }} btn-lg"
                            data-active="{{ $contestActive ? 'true' : 'false' }}">
                        <i class="bi {{ $contestActive ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                        {{ $contestActive ? '대회 비활성화' : '대회 활성화' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 진행률 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> 심사 진행률</h5>
    </div>
    <div class="card-body">
        @php
            $progressPercentage = $totalSubmissions > 0 ? round(($evaluatedSubmissions / $totalSubmissions) * 100, 1) : 0;
        @endphp
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>전체 진행률</span>
            <span class="fw-bold">{{ $progressPercentage }}%</span>
        </div>
        
        <div class="progress" style="height: 25px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 style="width: {{ $progressPercentage }}%"
                 aria-valuenow="{{ $progressPercentage }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                {{ $progressPercentage }}%
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-6 text-center">
                <small class="text-muted">심사 완료</small><br>
                <strong class="text-success">{{ $evaluatedSubmissions }}개</strong>
            </div>
            <div class="col-6 text-center">
                <small class="text-muted">배정 대기중 영상</small><br>
                <strong class="text-warning">{{ $pendingSubmissions }}개</strong>
            </div>
        </div>
    </div>
</div>

<!-- 빠른 작업 -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> 빠른 작업</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.assignment.list') }}" 
                       class="btn btn-admin">
                        <i class="bi bi-person-check"></i> 영상 배정 관리
                    </a>
                    <a href="{{ route('admin.evaluation.list', ['status' => 'pending']) }}" 
                       class="btn btn-admin">
                        <i class="bi bi-clipboard-check"></i> 심사 대기 목록 보기
                    </a>
                    <a href="{{ route('judge.dashboard') }}" 
                       class="btn btn-admin">
                        <i class="bi bi-person-badge"></i> 심사위원 페이지로 이동
                    </a>
                    </a>
                    
                    <a href="{{ route('admin.evaluation.list') }}" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-list-check"></i> 전체 접수 목록
                    </a>
                    
                    <a href="{{ route('admin.download.excel') }}" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i> 데이터 다운로드
                    </a>
                    
                    <a href="{{ route('admin.statistics') }}" 
                       class="btn btn-outline-info">
                        <i class="bi bi-graph-up"></i> 상세 통계 보기
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> 시스템 정보</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-server text-primary"></i>
                        <strong>시스템:</strong> Laravel {{ app()->version() }}
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-database text-info"></i>
                        <strong>데이터베이스:</strong> Supabase
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-calendar text-success"></i>
                        <strong>대회 기간:</strong> 진행 중
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-shield-check text-warning"></i>
                        <strong>보안:</strong> 활성화
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 최근 제출된 영상 -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 최근 접수된 영상</h5>
        <div class="d-flex gap-2">
            <button id="select-all-videos" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check-square"></i> 전체 선택
            </button>
            <button id="delete-selected-videos" class="btn btn-sm btn-danger" disabled>
                <i class="bi bi-trash"></i> 선택 삭제
            </button>
            <a href="{{ route('admin.evaluation.list') }}" class="btn btn-sm btn-outline-light">
                전체 보기 <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- 검색 영역 -->
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="학생명, 기관명, 접수번호, 파일명으로 검색..." 
                               value="{{ $searchQuery ?? '' }}"
                               id="search-input">
                        @if(!empty($searchQuery))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary" title="검색 초기화">
                            <i class="bi bi-x-circle"></i>
                        </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> 검색
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                @if(!empty($searchQuery))
                <div class="alert alert-info mb-0 py-2">
                    <i class="bi bi-info-circle"></i> 
                    "<strong>{{ $searchQuery }}</strong>" 검색 결과: <strong>{{ $recentSubmissions->total() }}</strong>개
                </div>
                @endif
            </div>
        </div>
        @if($recentSubmissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                            </th>
                            <th>접수번호</th>
                            <th>접수일</th>
                            <th>학생명</th>
                            <th>기관</th>
                            <th>파일</th>
                            <th>상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSubmissions as $submission)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input video-checkbox" 
                                       value="{{ $submission->id }}" 
                                       data-student-name="{{ $submission->student_name_korean }}">
                            </td>
                            <td>
                                <small>{{ $submission->receipt_number }}</small>
                            </td>
                            <td>
                                <small>{{ $submission->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $submission->institution_name }}<br>
                                <small class="text-muted">{{ $submission->class_name }}</small>
                            </td>
                            <td>
                                <i class="bi bi-camera-video text-primary"></i>
                                {{ Str::limit($submission->video_file_name, 20) }}<br>
                                <small class="text-muted">{{ $submission->getFormattedFileSizeAttribute() }}</small>
                            </td>
                            <td>
                                @if($submission->evaluation)
                                    <span class="badge badge-evaluated">
                                        <i class="bi bi-check-circle"></i> 심사완료
                                    </span>
                                @else
                                    <span class="badge badge-pending">
                                        <i class="bi bi-clock"></i> 대기중
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info" 
                                            onclick="showVideoModal({{ $submission->id }}, {{ json_encode($submission->student_name_korean) }}, {{ json_encode($submission->video_file_name) }})"
                                            title="영상 보기">
                                        <i class="bi bi-play-circle"></i> 영상
                                    </button>
                                    <a href="{{ route('admin.evaluation.show', $submission->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        @if($submission->evaluation)
                                            <i class="bi bi-eye"></i> 보기
                                        @else
                                            <i class="bi bi-clipboard-check"></i> 심사
                                        @endif
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                @if(!empty($searchQuery))
                    <i class="bi bi-search display-4 text-muted"></i>
                    <p class="text-muted mt-2">검색 결과가 없습니다.</p>
                    <p class="text-muted small">"<strong>{{ $searchQuery }}</strong>"에 해당하는 영상을 찾을 수 없습니다.</p>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> 검색 초기화
                    </a>
                @else
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">접수된 영상이 없습니다.</p>
                    <a href="{{ url('/') }}" class="btn btn-outline-primary" target="_blank">
                        <i class="bi bi-plus-circle"></i> 대회 페이지로 이동
                    </a>
                @endif
            </div>
        @endif
        
        <!-- 최근 접수된 영상 페이지네이션 -->
        @if($recentSubmissions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $recentSubmissions->appends(request()->query())->links('custom.pagination') }}
        </div>
        @endif
    </div>
</div>

{{-- 2차 예선 관리 - 2차 예선진출 기능이 필요 없어서 주석처리
<div class="card admin-card mb-4">
    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0 text-white">
            <i class="bi bi-trophy"></i> 
            2차 예선 관리
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- 2차 예선 진출자 선정 -->
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-primary">
                            <i class="bi bi-award"></i> 2차 예선 진출자 선정
                        </h6>
                        <p class="text-muted mb-2">
                            각 심사위원별로 상위 10명을 자동 선정합니다.
                        </p>
                        <ul class="text-muted small">
                            <li>심사위원: {{ $judgesCount }}명</li>
                            <li>완료된 심사: {{ $evaluatedSubmissions }}개</li>
                            <li>예상 진출자: 최대 {{ $judgesCount * 10 }}명</li>
                        </ul>
                    </div>
                    <div>
                        <form action="{{ route('admin.qualify.second.round') }}" method="POST" 
                              onsubmit="return confirm('각 심사위원별로 상위 10명을 2차 예선 진출자로 선정하시겠습니까?')">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-star-fill"></i> 진출자 선정
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 전체 학생 순위 조회 -->
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-primary">
                            <i class="bi bi-trophy"></i> 전체 학생 순위 조회
                        </h6>
                        <p class="text-muted mb-2">
                            모든 학생들의 종합 점수 순위를 확인합니다.
                        </p>
                        {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                        @php
                            $qualifiedCount = \App\Models\Evaluation::where('qualification_status', 'qualified')->count();
                        @endphp
                        <ul class="text-muted small">
                            <li>현재 진출자: {{ $qualifiedCount }}명</li>
                            <li>심사위원별 순위 표시</li>
                            <li>엑셀 다운로드 가능</li>
                        </ul>
                        --}}
                        <!-- <ul class="text-muted small">
                            <li>전체 학생 순위 표시</li>
                            <li>두 심사위원 점수 합계 기준</li>
                            <li>엑셀 다운로드 가능</li>
                        </ul> -->
                    </div>
                    <div>
                        {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                        <a href="{{ route('admin.second.round.qualifiers') }}" class="btn btn-success">
                            <i class="bi bi-eye"></i> 목록 보기
                        </a>
                        --}}
                        <a href="{{ route('admin.statistics') }}" class="btn btn-primary">
                            <i class="bi bi-bar-chart"></i> 전체 순위 보기
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 자격 상태 초기화 (2차 예선진출 기능이 필요 없어서 주석처리)
        <div class="row mt-3 pt-3 border-top">
            <div class="col-md-8">
                <h6 class="text-warning">
                    <i class="bi bi-arrow-clockwise"></i> 자격 상태 초기화
                </h6>
                <p class="text-muted mb-0">
                    모든 2차 예선 자격 상태를 초기화합니다.
                    <br><small class="text-warning">※ 선정된 진출자가 모두 대기 상태로 변경됩니다.</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <form action="{{ route('admin.reset.qualification') }}" method="POST" 
                      onsubmit="return confirm('모든 2차 예선 자격 상태를 초기화하시겠습니까? 이 작업은 되돌릴 수 없습니다.')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise"></i> 상태 초기화
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div> 
--}}

<!-- 주석 종료 -->

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
                               style="object-fit: contain;"
                               crossorigin="anonymous">
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

<!-- 위험 구역 -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card admin-card mb-5 my-4">
    <!-- <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
        <h6 class="mb-0 text-white">
            <i class="bi bi-exclamation-triangle"></i> 
            위험 구역
        </h6>
    </div> -->
    <div class="card-body py-4">
        <div class="alert alert-danger mb-4">
            <h6 class="alert-heading fs-6">
                <i class="bi bi-shield-exclamation"></i> 주의사항
            </h6>
            <p class="mb-0 small">
                아래 기능들은 시스템에 중대한 영향을 미칠 수 있습니다. 
                <strong>실행 전 반드시 백업하고 신중하게 검토하세요.</strong>
            </p>
        </div>
        
        <div class="d-grid gap-2">
            <a href="{{ route('admin.reset.confirmation') }}" class="btn btn-danger btn-sm py-1">
                <i class="bi bi-trash"></i> 전체 데이터 초기화
            </a>
        </div>
        </div>
    </div>
</div>



@push('scripts')
<script>
// 검색 입력 필드에서 Enter 키 처리
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
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
        console.log('API 응답 상태:', response.status); // 디버깅용
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
        console.log('영상 데이터:', data); // 디버깅용
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
        videoSource.src = '';
        videoSource.type = '';
        videoPlayer.load();
    }, { once: true });
}

function clearSystemCache() {
    if (confirm('시스템 캐시를 정리하시겠습니까?')) {
        fetch('/admin/clear-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('캐시가 성공적으로 정리되었습니다.');
                location.reload();
            } else {
                alert('캐시 정리 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        });
    }
}

function optimizeSystem() {
    if (confirm('시스템 최적화를 실행하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 실행 중...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = '<i class="bi bi-check"></i> 완료!';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }, 3000);
    }
}

function manageLogs() {
    if (confirm('로그 파일을 정리하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 정리 중...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = '<i class="bi bi-check"></i> 완료!';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }, 2000);
    }
}

// 영상 삭제 관련 기능
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const videoCheckboxes = document.querySelectorAll('.video-checkbox');
    const deleteButton = document.getElementById('delete-selected-videos');
    const selectAllButton = document.getElementById('select-all-videos');

    // 전체 선택 체크박스 이벤트
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            videoCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButton();
        });
    }

    // 전체 선택 버튼 이벤트
    if (selectAllButton) {
        selectAllButton.addEventListener('click', function() {
            const allChecked = Array.from(videoCheckboxes).every(cb => cb.checked);
            videoCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            selectAllCheckbox.checked = !allChecked;
            updateDeleteButton();
        });
    }

    // 개별 체크박스 이벤트
    videoCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox();
            updateDeleteButton();
        });
    });

    // 삭제 버튼 이벤트
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            const selectedIds = Array.from(videoCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('삭제할 영상을 선택해주세요.');
                return;
            }

            const selectedNames = Array.from(videoCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.dataset.studentName);

            if (confirm(`선택한 ${selectedIds.length}개의 영상을 삭제하시겠습니까?\n\n학생: ${selectedNames.join(', ')}`)) {
                deleteSelectedVideos(selectedIds);
            }
        });
    }

    function updateSelectAllCheckbox() {
        if (selectAllCheckbox) {
            const checkedCount = Array.from(videoCheckboxes).filter(cb => cb.checked).length;
            selectAllCheckbox.checked = checkedCount === videoCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < videoCheckboxes.length;
        }
    }

    function updateDeleteButton() {
        if (deleteButton) {
            const checkedCount = Array.from(videoCheckboxes).filter(cb => cb.checked).length;
            deleteButton.disabled = checkedCount === 0;
            deleteButton.innerHTML = checkedCount > 0 
                ? `<i class="bi bi-trash"></i> 선택 삭제 (${checkedCount})`
                : `<i class="bi bi-trash"></i> 선택 삭제`;
        }
    }

    function deleteSelectedVideos(ids) {
        const button = deleteButton;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 삭제 중...';
        button.disabled = true;

        fetch('{{ route("admin.videos.delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${data.deleted_count}개의 영상이 삭제되었습니다.`);
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    // 대회 활성화 토글 기능
    const contestToggleBtn = document.getElementById('contest-toggle-btn');
    if (contestToggleBtn) {
        contestToggleBtn.addEventListener('click', function() {
            const isActive = this.dataset.active === 'true';
            const action = isActive ? '비활성화' : '활성화';
            
            if (confirm(`대회를 ${action}하시겠습니까?`)) {
                toggleContestStatus();
            }
        });
    }

    function toggleContestStatus() {
        const button = document.getElementById('contest-toggle-btn');
        const originalText = button.innerHTML;
        
        // 버튼 비활성화 및 로딩 표시
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> 처리 중...';
        
        // CSRF 토큰을 동적으로 새로 가져오기
        fetch('/admin/csrf-token', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const csrfToken = data.csrf_token;
            console.log('Fresh CSRF Token (button):', csrfToken);
            
            return fetch('{{ route("admin.contest.toggle") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: '_token=' + encodeURIComponent(csrfToken)
            });
        })
        .then(response => {
            console.log('Button response status:', response.status);
            console.log('Button response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // 응답이 JSON인지 확인
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // HTML 응답인 경우 로그인 페이지로 리다이렉트
                if (contentType && contentType.includes('text/html')) {
                    alert('세션이 만료되었습니다. 다시 로그인해주세요.');
                    window.location.href = '{{ route("admin.login") }}';
                    return;
                }
                throw new Error(`Expected JSON response, got: ${contentType}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 성공 시 페이지 새로고침
                location.reload();
            } else {
                alert('오류: ' + data.message);
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    // 대회 상태 토글 기능
    const contestToggle = document.getElementById('contestToggle');
    const contestStatusLabel = document.getElementById('contestStatusLabel');
    
    if (contestToggle) {
        contestToggle.addEventListener('change', function() {
            const isActive = this.checked;
            const originalText = contestStatusLabel.textContent;
            
            // 로딩 상태 표시
            contestStatusLabel.textContent = '변경 중...';
            contestToggle.disabled = true;
            
            // CSRF 토큰을 동적으로 새로 가져오기
            fetch('{{ route("admin.csrf-token") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const csrfToken = data.csrf_token;
                console.log('Fresh CSRF Token:', csrfToken);
                
                return fetch('{{ route("admin.contest.toggle") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: '_token=' + encodeURIComponent(csrfToken)
                });
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // 응답이 JSON인지 확인
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // HTML 응답인 경우 로그인 페이지로 리다이렉트
                    if (contentType && contentType.includes('text/html')) {
                        alert('세션이 만료되었습니다. 다시 로그인해주세요.');
                        window.location.href = '{{ route("admin.login") }}';
                        return;
                    }
                    throw new Error(`Expected JSON response, got: ${contentType}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // 성공 시 UI 업데이트
                    contestStatusLabel.textContent = data.contest_active ? '활성화' : '비활성화';
                    contestToggle.checked = data.contest_active;
                    
                    // 성공 메시지 표시
                    showAlert('success', data.message);
                } else {
                    // 실패 시 원래 상태로 복원
                    contestToggle.checked = !isActive;
                    contestStatusLabel.textContent = originalText;
                    showAlert('danger', data.message || '대회 상태 변경에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Detailed error:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                
                // 실패 시 원래 상태로 복원
                contestToggle.checked = !isActive;
                contestStatusLabel.textContent = originalText;
                showAlert('danger', `네트워크 오류가 발생했습니다: ${error.message}`);
            })
            .finally(() => {
                contestToggle.disabled = false;
            });
        });
    }
    
    // 알림 표시 함수
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // 5초 후 자동 제거
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }
    
    function createAlertContainer() {
        const container = document.createElement('div');
        container.id = 'alertContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
        return container;
    }
});
</script>
@endpush



@endsection