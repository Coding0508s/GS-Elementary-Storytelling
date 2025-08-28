@extends('admin.layout')

@section('title', '기관명 관리')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">🏢 기관명 관리</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstitutionModal">
                    <i class="bi bi-plus-circle"></i> 기관명 추가
                </button>
            </div>

            <!-- 알림 메시지는 layout.blade.php에서 처리됩니다 -->

            <!-- 기관명 목록 -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">등록된 기관명 (총 {{ $institutions->total() }}개)</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('admin.institution.list') }}" class="d-flex gap-2">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control form-control-sm" 
                                           name="search" 
                                           value="{{ request('search') }}" 
                                           placeholder="기관명 검색...">
                                    <select name="type" class="form-select form-select-sm" style="max-width: 120px;">
                                        <option value="">전체 유형</option>
                                        <option value="kindergarten" {{ request('type') == 'kindergarten' ? 'selected' : '' }}>유치원</option>
                                        <option value="academy" {{ request('type') == 'academy' ? 'selected' : '' }}>학원</option>
                                        <option value="elementary" {{ request('type') == 'elementary' ? 'selected' : '' }}>초등학교</option>
                                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>기타</option>
                                    </select>
                                    <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    @if(request('search') || request('type'))
                                        <a href="{{ route('admin.institution.list') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($institutions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="8%">순서</th>
                                        <th width="40%">기관명</th>
                                        <th width="15%">유형</th>
                                        <th width="12%">제출 건수</th>
                                        <th width="10%">상태</th>
                                        <th width="15%">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($institutions as $institution)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $institution->sort_order }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $institution->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ App\Models\Institution::TYPES[$institution->type] ?? $institution->type }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($institution->video_submissions_count > 0)
                                                <span class="badge bg-primary">{{ $institution->video_submissions_count }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($institution->is_active)
                                                <span class="badge bg-success">활성</span>
                                            @else
                                                <span class="badge bg-secondary">비활성</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- 수정 버튼 -->
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editInstitutionModal"
                                                        data-id="{{ $institution->id }}"
                                                        data-name="{{ $institution->name }}"
                                                        data-type="{{ $institution->type }}"
                                                        data-description="{{ $institution->description }}"
                                                        data-sort-order="{{ $institution->sort_order }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- 활성화/비활성화 토글 -->
                                                <form method="POST" action="{{ route('admin.institution.toggle', $institution->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-{{ $institution->is_active ? 'warning' : 'success' }} btn-sm"
                                                            onclick="return confirm('{{ $institution->is_active ? '비활성화' : '활성화' }}하시겠습니까?')">
                                                        <i class="bi bi-{{ $institution->is_active ? 'eye-slash' : 'eye' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- 삭제 버튼 -->
                                                @if($institution->video_submissions_count == 0)
                                                    <form method="POST" action="{{ route('admin.institution.delete', $institution->id) }}" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                onclick="return confirm('정말로 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                            title="영상 제출이 있는 기관은 삭제할 수 없습니다">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- 페이지네이션 -->
                        <div class="card-footer bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    {{ $institutions->links('custom.pagination') }}
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-md-end justify-content-center">
                                        <div class="input-group" style="width: auto;">
                                            <span class="input-group-text">페이지당</span>
                                            <select class="form-select" id="perPageSelect" style="width: 80px;">
                                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                                <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                            <span class="input-group-text">개씩</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">등록된 기관이 없습니다.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstitutionModal">
                                첫 번째 기관 추가하기
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 기관명 추가 모달 -->
<div class="modal fade" id="addInstitutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.institution.add') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">기관명 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">기관명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                        <div class="form-text">기관의 정식 명칭을 입력해주세요.</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_type" class="form-label">기관 유형 <span class="text-danger">*</span></label>
                        <select class="form-control" id="add_type" name="type" required>
                            <option value="">유형을 선택해주세요</option>
                            @foreach(App\Models\Institution::TYPES as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label">설명</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3" 
                                  placeholder="기관에 대한 추가 설명 (선택사항)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="add_sort_order" class="form-label">정렬 순서</label>
                        <input type="number" class="form-control" id="add_sort_order" name="sort_order" 
                               min="0" value="0">
                        <div class="form-text">숫자가 낮을수록 먼저 표시됩니다.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">추가</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 기관명 수정 모달 -->
<div class="modal fade" id="editInstitutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editInstitutionForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">기관명 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">기관명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_type" class="form-label">기관 유형 <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_type" name="type" required>
                            @foreach(App\Models\Institution::TYPES as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">설명</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">정렬 순서</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">수정</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 수정 모달 데이터 설정
    const editModal = document.getElementById('editInstitutionModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const type = button.getAttribute('data-type');
        const description = button.getAttribute('data-description');
        const sortOrder = button.getAttribute('data-sort-order');
        
        // 폼 액션 URL 설정
        document.getElementById('editInstitutionForm').action = `/admin/institutions/${id}`;
        
        // 폼 필드 값 설정
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_description').value = description || '';
        document.getElementById('edit_sort_order').value = sortOrder || 0;
    });
    
    // 페이지당 항목 수 변경
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page'); // 페이지를 1로 리셋
            window.location.href = url.toString();
        });
    }
    
    // 실시간 검색 (디바운스 적용)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                // 3글자 이상일 때만 자동 검색
                if (this.value.length >= 3 || this.value.length === 0) {
                    const form = this.closest('form');
                    const url = new URL(form.action);
                    
                    // 검색어 설정
                    if (this.value.trim()) {
                        url.searchParams.set('search', this.value.trim());
                    } else {
                        url.searchParams.delete('search');
                    }
                    
                    // 기존 필터 유지
                    const typeSelect = form.querySelector('select[name="type"]');
                    if (typeSelect.value) {
                        url.searchParams.set('type', typeSelect.value);
                    }
                    
                    // 페이지당 항목 수 유지
                    const perPage = document.getElementById('perPageSelect');
                    if (perPage) {
                        url.searchParams.set('per_page', perPage.value);
                    }
                    
                    url.searchParams.delete('page'); // 페이지를 1로 리셋
                    window.location.href = url.toString();
                }
            }, 500); // 500ms 디바운스
        });
    }
});
</script>

@endsection
