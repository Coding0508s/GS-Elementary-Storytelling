<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '연사 초청 세미나 - GrapeSEED')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/seminar.css') }}">
</head>
<body>
    <div class="container">
        <div class="contest-container">
            <div class="header-section">
                <div class="logo-container">
                    <img src="{{ asset('images/grape-seed-logo.png') }}" alt="GrapeSEED English for Children" class="grape-seed-logo">
                </div>
                <h1><span class="contest-title">연사 초청 세미나</span></h1>
                <!-- <p>GrapeSEED 학생들의 특별한 2025 Speech Contest</p> -->
            </div>
            
            @if(session('success'))
                <div id="success-banner" class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="알림 닫기"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="알림 닫기"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="알림 닫기"></button>
                </div>
            @endif
            
            <div class="form-section">
                @yield('content')
            </div>
            
            <div class="footer-section">
                <p>&copy; {{ now()->year }} GrapeSEED English for Children. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 초록 성공 배너는 읽을 시간을 준 뒤 스스로 닫힙니다.
        document.addEventListener('DOMContentLoaded', function () {
            const banner = document.getElementById('success-banner');
            if (!banner || typeof bootstrap === 'undefined') {
                return;
            }

            window.setTimeout(function () {
                bootstrap.Alert.getOrCreateInstance(banner).close();
            }, 4000);
        });
    </script>

    @yield('scripts')
</body>
</html> 