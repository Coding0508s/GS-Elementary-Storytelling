@extends('layouts.event')

@section('title', '대회 비활성화')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center py-5">
                    <!-- 아이콘 -->
                    <div class="mb-4">
                        <i class="bi bi-pause-circle display-1 text-warning"></i>
                    </div>
                    
                    <!-- 제목 -->
                    <h1 class="display-4 fw-bold text-dark mb-3">
                        대회가 일시 중단되었습니다
                    </h1>
                    
                    <!-- 설명 -->
                    <p class="lead text-muted mb-4">
                        현재 대회가 일시적으로 중단된 상태입니다.<br>
                        대회가 다시 시작되면 이 페이지에서 알려드리겠습니다.
                    </p>
                    
                    <!-- 추가 정보 -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <div class="text-start">
                                <strong>안내사항:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>대회 재개 시 기존에 업로드된 영상은 그대로 유지됩니다</li>
                                    <li>대회 관련 문의사항이 있으시면 관리자에게 연락해주세요</li>
                                    <li>정기적으로 이 페이지를 확인해주시기 바랍니다</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 새로고침 버튼 -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button class="btn btn-primary btn-lg px-4" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            새로고침
                        </button>
                        <button class="btn btn-outline-secondary btn-lg px-4" onclick="history.back()">
                            <i class="bi bi-arrow-left me-2"></i>
                            이전 페이지
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 푸터 정보 -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    마지막 확인: {{ now()->format('Y년 m월 d일 H:i') }}
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 20px;
}

.display-1 {
    font-size: 4rem;
}

.alert {
    border-radius: 15px;
}

.btn {
    border-radius: 10px;
    font-weight: 600;
}

.btn-lg {
    padding: 12px 30px;
}
</style>
@endsection
