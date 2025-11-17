@extends('admin.layout')

@section('title', '휴지통')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-trash"></i> 휴지통</h1>
        <p class="text-muted mb-0">삭제된 영상 목록입니다. 복원하거나 영구 삭제할 수 있습니다.</p>
    </div>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
        </a>
    </div>
</div>

<!-- 검색 영역 -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.trash.list') }}" class="d-flex gap-2">
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
                <a href="{{ route('admin.trash.list') }}" class="btn btn-outline-secondary" title="검색 초기화">
                    <i class="bi bi-x-circle"></i>
                </a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> 검색
                </button>
            </div>
        </form>
        @if(!empty($searchQuery))
        <div class="mt-2">
            <div class="alert alert-info mb-0 py-2">
                <i class="bi bi-info-circle"></i> 
                "<strong>{{ $searchQuery }}</strong>" 검색 결과: <strong>{{ $trashedSubmissions->total() }}</strong>개
            </div>
        </div>
        @endif
    </div>
</div>

<!-- 휴지통 영상 목록 -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-trash"></i> 삭제된 영상 ({{ $trashedSubmissions->total() }}개)</h5>
        <div class="d-flex gap-2">
            <button id="select-all-videos" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check-square"></i> 전체 선택
            </button>
            <button id="restore-selected-videos" class="btn btn-sm btn-success" disabled>
                <i class="bi bi-arrow-counterclockwise"></i> 선택 복원
            </button>
            <button id="delete-selected-videos" class="btn btn-sm btn-danger" disabled>
                <i class="bi bi-trash"></i> 선택 영구 삭제
            </button>
        </div>
    </div>
    <div class="card-body">
        @if($trashedSubmissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                            </th>
                            <th>접수번호</th>
                            <th>삭제일</th>
                            <th>학생명</th>
                            <th>기관</th>
                            <th>파일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedSubmissions as $submission)
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
                                <small>{{ $submission->deleted_at->format('Y-m-d H:i') }}</small>
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
                                <div class="btn-group" role="group">
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            onclick="restoreVideo({{ $submission->id }}, '{{ $submission->student_name_korean }}')"
                                            title="복원">
                                        <i class="bi bi-arrow-counterclockwise"></i> 복원
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="forceDeleteVideo({{ $submission->id }}, '{{ $submission->student_name_korean }}')"
                                            title="영구 삭제">
                                        <i class="bi bi-trash"></i> 삭제
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            @if($trashedSubmissions->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $trashedSubmissions->appends(request()->query())->links('custom.pagination') }}
            </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-trash display-4 text-muted"></i>
                <p class="text-muted mt-3">휴지통이 비어있습니다.</p>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
                </a>
            </div>
        @endif
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

    // 전체 선택 체크박스 이벤트
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const videoCheckboxes = document.querySelectorAll('.video-checkbox');
    const restoreButton = document.getElementById('restore-selected-videos');
    const deleteButton = document.getElementById('delete-selected-videos');
    const selectAllButton = document.getElementById('select-all-videos');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            videoCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateButtons();
        });
    }

    if (selectAllButton) {
        selectAllButton.addEventListener('click', function() {
            const allChecked = Array.from(videoCheckboxes).every(cb => cb.checked);
            videoCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            selectAllCheckbox.checked = !allChecked;
            updateButtons();
        });
    }

    videoCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox();
            updateButtons();
        });
    });

    function updateSelectAllCheckbox() {
        if (selectAllCheckbox) {
            const checkedCount = Array.from(videoCheckboxes).filter(cb => cb.checked).length;
            selectAllCheckbox.checked = checkedCount === videoCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < videoCheckboxes.length;
        }
    }

    function updateButtons() {
        const checkedCount = Array.from(videoCheckboxes).filter(cb => cb.checked).length;
        if (restoreButton) {
            restoreButton.disabled = checkedCount === 0;
            restoreButton.innerHTML = checkedCount > 0 
                ? `<i class="bi bi-arrow-counterclockwise"></i> 선택 복원 (${checkedCount})`
                : `<i class="bi bi-arrow-counterclockwise"></i> 선택 복원`;
        }
        if (deleteButton) {
            deleteButton.disabled = checkedCount === 0;
            deleteButton.innerHTML = checkedCount > 0 
                ? `<i class="bi bi-trash"></i> 선택 영구 삭제 (${checkedCount})`
                : `<i class="bi bi-trash"></i> 선택 영구 삭제`;
        }
    }

    // 선택 복원 버튼 이벤트
    if (restoreButton) {
        restoreButton.addEventListener('click', function() {
            const selectedIds = Array.from(videoCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('복원할 영상을 선택해주세요.');
                return;
            }

            if (confirm(`선택한 ${selectedIds.length}개의 영상을 복원하시겠습니까?`)) {
                restoreSelectedVideos(selectedIds);
            }
        });
    }

    // 선택 영구 삭제 버튼 이벤트
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

            if (confirm(`⚠️ 경고: 선택한 ${selectedIds.length}개의 영상을 영구적으로 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.\n\n학생: ${selectedNames.join(', ')}`)) {
                if (confirm('🔴 최종 확인: 정말로 영구 삭제하시겠습니까?')) {
                    forceDeleteSelectedVideos(selectedIds);
                }
            }
        });
    }
});

// 개별 영상 복원
function restoreVideo(id, studentName) {
    if (confirm(`"${studentName}"의 영상을 복원하시겠습니까?`)) {
        fetch(`/admin/trash/restore/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        });
    }
}

// 개별 영상 영구 삭제
function forceDeleteVideo(id, studentName) {
    if (confirm(`⚠️ 경고: "${studentName}"의 영상을 영구적으로 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.`)) {
        if (confirm('🔴 최종 확인: 정말로 영구 삭제하시겠습니까?')) {
            fetch(`/admin/trash/force-delete/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('오류: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('네트워크 오류가 발생했습니다.');
            });
        }
    }
}

// 선택된 영상 복원
function restoreSelectedVideos(ids) {
    const button = document.getElementById('restore-selected-videos');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 복원 중...';
    button.disabled = true;

    fetch('{{ route("admin.trash.restore.selected") }}', {
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
            alert(data.message);
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

// 선택된 영상 영구 삭제
function forceDeleteSelectedVideos(ids) {
    const button = document.getElementById('delete-selected-videos');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 삭제 중...';
    button.disabled = true;

    fetch('{{ route("admin.trash.force.delete.selected") }}', {
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
            alert(data.message);
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
</script>
@endpush
@endsection

