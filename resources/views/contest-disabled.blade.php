@extends('layouts.event')

@section('title', '2025 GrapeSEED Storytelling 이벤트 종료')

@section('content')
<div class="container mt-5 p-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-3 col-sm-12">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center py-5">
                    <!-- 아이콘 -->
                    <div class="mb-4">
                        <i class="bi bi-pause-circle display-1 text-warning"></i>
                    </div>
                    
                    <!-- 제목 -->
                    <h3 class="display-4 fw-bold text-dark mb-5">
                        2025 GrapeSEED Storytelling 이벤트 종료
                    </h3>
                    
                    <!-- 설명 -->
                    <p class="lead text-muted mb-4">
                        현재 2025 GrapeSEED Storytelling 이벤트가 종료되었습니다.<br>
                
                    </p>
                    
                    <!-- 추가 정보 -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="text-center">
                           <!--  <i class="bi bi-info-circle me-2"></i> -->
                            <div class="text-center">
                                <strong>2025 GrapeSEED Storytelling 이벤트에 참여해주셔서 감사합니다!</strong>
                                <!-- <ul class="mb-0 mt-2">
                                    <li>2025 GrapeSEED Storytelling 이벤트에 참여해주셔서 감사합니다.</li>
                                </ul> -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- 새로고침 버튼 -->
                    <!-- <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button class="btn btn-primary btn-lg px-4" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            새로고침
                        </button>
                        <button class="btn btn-outline-secondary btn-lg px-4" onclick="history.back()">
                            <i class="bi bi-arrow-left me-2"></i>
                            이전 페이지
                        </button>
                    </div> -->
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
