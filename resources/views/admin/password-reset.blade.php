@extends('admin.layout')

@section('title', '비밀번호 재설정')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-key-fill me-2"></i>비밀번호 재설정
                    </h5>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- 알림 메시지는 layout.blade.php에서 처리됩니다 -->

                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <form method="POST" action="{{ route('admin.password.reset.execute') }}">
                                @csrf
                                
                                <!-- 계정 선택 -->
                                <div class="mb-4">
                                    <label for="admin_id" class="form-label">
                                        <i class="bi bi-person-fill me-1"></i>비밀번호를 변경할 계정 선택
                                    </label>
                                    <select class="form-select @error('admin_id') is-invalid @enderror" 
                                            id="admin_id" name="admin_id" required>
                                        <option value="">계정을 선택하세요</option>
                                        @foreach($allAdmins as $admin)
                                            <option value="{{ $admin->id }}" 
                                                    {{ old('admin_id') == $admin->id ? 'selected' : '' }}>
                                                {{ $admin->name }} ({{ $admin->username }}) - 
                                                @if($admin->role === 'admin')
                                                    <span class="text-danger">관리자</span>
                                                @else
                                                    <span class="text-primary">심사위원</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('admin_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- 새 비밀번호 -->
                                <div class="mb-4">
                                    <label for="new_password" class="form-label">
                                        <i class="bi bi-lock-fill me-1"></i>새 비밀번호
                                    </label>
                                    <input type="password" 
                                           class="form-control @error('new_password') is-invalid @enderror" 
                                           id="new_password" name="new_password" 
                                           placeholder="새 비밀번호를 입력하세요 (최소 6자)"
                                           minlength="6" required>
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        비밀번호는 최소 6자 이상이어야 합니다.
                                    </div>
                                </div>

                                <!-- 비밀번호 확인 -->
                                <div class="mb-4">
                                    <label for="new_password_confirmation" class="form-label">
                                        <i class="bi bi-lock-fill me-1"></i>새 비밀번호 확인
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password_confirmation" name="new_password_confirmation" 
                                           placeholder="새 비밀번호를 다시 입력하세요"
                                           minlength="6" required>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        동일한 비밀번호를 다시 입력해주세요.
                                    </div>
                                </div>

                                <!-- 경고 메시지 -->
                                <div class="alert alert-warning" role="alert">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>주의사항
                                    </h6>
                                    <ul class="mb-0">
                                        <li>비밀번호 변경 후 해당 사용자는 새 비밀번호로 로그인해야 합니다.</li>
                                        <li>안전한 비밀번호를 사용하세요 (영문, 숫자, 특수문자 조합 권장).</li>
                                        <li>비밀번호 변경 내역은 시스템 로그에 기록됩니다.</li>
                                    </ul>
                                </div>

                                <!-- 버튼 그룹 -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-outline-secondary me-md-2" 
                                            onclick="window.location.href='{{ route('admin.dashboard') }}'">
                                        <i class="bi bi-x-circle me-1"></i>취소
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-key-fill me-1"></i>비밀번호 변경
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 계정 정보 카드 -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-people-fill me-2"></i>현재 등록된 계정 목록
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>이름</th>
                                    <th>사용자명</th>
                                    <th>이메일</th>
                                    <th>역할</th>
                                    <th>상태</th>
                                    <th>마지막 로그인</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allAdmins as $admin)
                                <tr>
                                    <td>{{ $admin->id }}</td>
                                    <td>
                                        <strong>{{ $admin->name }}</strong>
                                    </td>
                                    <td>
                                        <code>{{ $admin->username }}</code>
                                    </td>
                                    <td>{{ $admin->email }}</td>
                                    <td>
                                        @if($admin->role === 'admin')
                                            <span class="badge bg-danger">관리자</span>
                                        @else
                                            <span class="badge bg-primary">심사위원</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($admin->is_active)
                                            <span class="badge bg-success">활성</span>
                                        @else
                                            <span class="badge bg-secondary">비활성</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($admin->last_login_at)
                                            <small class="text-muted">
                                                {{ $admin->last_login_at->format('Y-m-d H:i') }}
                                            </small>
                                        @else
                                            <small class="text-muted">로그인 기록 없음</small>
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
    </div>
</div>

<script>
// 비밀번호 확인 실시간 체크
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');
    
    function checkPasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('비밀번호가 일치하지 않습니다.');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    password.addEventListener('input', checkPasswordMatch);
    confirmPassword.addEventListener('input', checkPasswordMatch);
});
</script>
@endsection
