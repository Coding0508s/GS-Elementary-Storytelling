<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '2025 GrapeSEED Speak and Shine')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 25%, #3b73d1 50%, #4a90e2 75%, #1e3c72 100%);
            min-height: 100vh;
        }
        
        /* 네비게이션 바 */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* 콘텐츠 영역 */
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
        
        /* 배경 효과 */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.4) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.2) 1px, transparent 1px),
                radial-gradient(circle at 50% 50%, rgba(255, 221, 89, 0.2) 3px, transparent 3px);
            background-size: 100px 100px, 60px 60px, 140px 140px;
            animation: float 30s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* 버튼 애니메이션 */
        .btn-hover-effect {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-hover-effect::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-hover-effect:hover::before {
            left: 100%;
        }
        
        /* 카드 효과 */
        .card-glassmorphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        /* 스크롤바 숨기기 */
        ::-webkit-scrollbar {
            display: none;
        }
        
        /* Firefox에서 스크롤바 숨기기 */
        html {
            scrollbar-width: none;
        }
        
        /* 페이지 전환 효과를 위한 기본 스타일 */
        body {
            transition: opacity 0.3s ease;
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- 배경 패턴 -->
    <div class="bg-pattern"></div>
    

    
    <!-- 메인 콘텐츠 -->
    <main class="content-wrapper">
        @if(session('success'))
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        
        @if($errors->any())
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        
        @yield('content')
    </main>
    
    <!-- 푸터 -->
   <!--  <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-2">
                        <img src="{{ asset('images/grape-seed-logo.png') }}" alt="GrapeSEED" height="20" class="me-2">
                        GrapeSEED English for Children
                    </h6>
                    <p class="small mb-0">
                        2025 Speak and Shine Contest<br>
                        영어를 배우는 모든 어린이들을 위한 특별한 경연대회
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-1">
                        <i class="bi bi-envelope"></i> 문의사항이 있으시면 언제든 연락주세요
                    </p>
                    <p class="small text-muted mb-0">
                        &copy; 2025 GrapeSEED. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
     -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 공통 스크립트 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 부드러운 스크롤 효과
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // 네비게이션 활성화
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>
