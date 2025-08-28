<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '관리자 페이지') - GS Elementary Speech Contest</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: 'Noto Sans KR', sans-serif;
            overflow-x: hidden; /* 가로 스크롤 방지 */
        }
        
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px; /* 고정 너비로 변경 */
            z-index: 1020;
            overflow-y: auto;
        }
        
        @media (min-width: 1200px) {
            .sidebar {
                width: 260px; /* 큰 화면에서는 더 좁게 */
            }
        }
        
        .logo-container {
            text-align: center;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 0.5rem;
        }
        
        .logo-container img {
            max-height: 50px;
            width: auto;
            filter: brightness(0) invert(1);
            margin-bottom: 0.5rem;
        }
        
        .logo-container .brand-text {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
            margin: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.65rem 0.75rem;
            margin: 0.1rem 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
        
        .main-content {
            padding: 2rem 2rem 6rem 2rem; /* 하단 여백 크게 증가 */
            margin-left: 280px; /* 사이드바 너비에 맞춤 */
            width: auto; /* 자동 너비 */
            max-width: none; /* 최대 너비 제한 해제 */
            overflow-x: hidden; /* 가로 스크롤 방지 */
            overflow-y: visible; /* 스크롤 제한 해제 */
            box-sizing: border-box; /* 패딩 포함한 너비 계산 */
            position: relative; /* 상대 위치 지정 */
        }
        
        @media (min-width: 1200px) {
            .main-content {
                margin-left: 260px; /* 큰 화면에서 사이드바 너비 맞춤 */
                width: auto; /* 자동 너비 */
                max-width: none; /* 최대 너비 제한 해제 */
                padding: 2rem 2rem 6rem 2rem; /* 하단 여백 크게 증가 */
                overflow-y: visible; /* 스크롤 제한 해제 */
            }
        }
        
        @media (max-width: 1199px) and (min-width: 768px) {
            .main-content {
                margin-left: 280px; /* 태블릿에서도 사이드바 너비에 맞춤 */
                width: auto; /* 자동 너비 */
                max-width: none; /* 최대 너비 제한 해제 */
                padding: 1.5rem 1.5rem 6rem 1.5rem; /* 하단 여백 크게 증가 */
                overflow-y: visible; /* 스크롤 제한 해제 */
            }
        }
        
        /* 모바일에서는 사이드바를 숨기고 토글 방식으로 */
        @media (max-width: 767px) {
            .sidebar {
                width: 280px; /* 모바일에서도 고정 너비 */
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100vw; /* viewport 전체 너비 */
                max-width: 100vw;
                padding: 0.5rem 1rem 3rem 1rem; /* 모바일에서도 하단 여백 증가 */
            }
        }
        
        .admin-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: visible; /* 카드 내용이 잘리지 않도록 */
        }
        
        .admin-card .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }
        
        .stats-card {
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .btn-admin {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .table-admin {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .table-admin th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        
        .badge-evaluated {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .badge-pending {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .score-input {
            width: 80px;
            text-align: center;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .user-info .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-info .user-role {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }
        
        /* 테이블 반응형 처리 */
        .table-responsive {
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            padding: 1rem 0.75rem;
            white-space: nowrap;
        }
        
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        /* 작은 화면에서 테이블 스크롤 개선 */
        @media (max-width: 991px) {
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.4rem;
            }
        }
        
        /* 모달 상단 여백 조정 */
        .modal-dialog {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 767px) {
            .modal-dialog {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        /* 컨테이너 관련 스타일 */
        .container-fluid {
            padding: 0;
            max-width: none;
            width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        
        /* 카드 본문 스타일 개선 */
        .admin-card .card-body {
            padding: 1.5rem;
        }
        
        /* 시스템 관리 카드 특별 스타일 */
        .admin-card:last-child {
            margin-bottom: 3rem; /* 마지막 카드는 더 큰 하단 여백 */
        }
        
        /* 반응형 개선 */
        @media (max-width: 991px) {
            .admin-card .card-body {
                padding: 1rem !important;
            }
            
            .card .card-body {
                padding: 1rem !important;
            }
            
            .fs-4 {
                font-size: 1.2rem !important;
            }
        }
        
        /* 매우 작은 화면 (576px 이하) */
        @media (max-width: 575px) {
            .admin-card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 0.75rem !important;
            }
            
            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid p-0">
        <!-- 사이드바 -->
        <div class="sidebar">
                    <div class="logo-container">
                        <img src="{{ asset('images/grape-seed-logo.png') }}" alt="GrapeSEED English for Children" class="grape-seed-logo">
                        <p class="brand-text">Speech Contest</p>
                    </div>
                    
                    @auth('admin')
                    <div class="user-info">
                        <div class="user-name">{{ Auth::guard('admin')->user()->name }}</div>
                        <div class="user-role">
                            @if(Auth::guard('admin')->user()->isAdmin())
                                <i class="bi bi-shield-check"></i> 관리자
                            @else
                                <i class="bi bi-person-check"></i> 심사위원
                            @endif
                        </div>
                        <small class="text-muted d-block mt-1">
                            마지막 로그인: {{ Auth::guard('admin')->user()->last_login_at ? Auth::guard('admin')->user()->last_login_at->format('m/d H:i') : '처음' }}
                        </small>
                    </div>
                    @endauth
                    
                    <nav class="nav flex-column px-3">
                        @if(Auth::guard('admin')->user()->isAdmin())
                        <!-- 관리자 전용 메뉴 -->
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> 대시보드
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.evaluation.*') ? 'active' : '' }}" 
                           href="{{ route('admin.evaluation.list') }}">
                            <i class="bi bi-clipboard-check"></i> 심사 관리
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.assignment.*') ? 'active' : '' }}" 
                           href="{{ route('admin.assignment.list') }}">
                            <i class="bi bi-person-check"></i> 영상 배정 관리
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.statistics') ? 'active' : '' }}" 
                           href="{{ route('admin.statistics') }}">
                            <i class="bi bi-graph-up"></i> 통계 보기
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.institution.*') ? 'active' : '' }}" 
                           href="{{ route('admin.institution.list') }}">
                            <i class="bi bi-building"></i> 기관명 관리
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.password.*') ? 'active' : '' }}" 
                           href="{{ route('admin.password.reset') }}">
                            <i class="bi bi-key-fill"></i> 비밀번호 재설정
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.judge.*') ? 'active' : '' }}" 
                           href="{{ route('admin.judge.management') }}">
                            <i class="bi bi-people-fill"></i> 심사위원 관리
                        </a>
                        
                        {{-- 2차 예선진출 기능이 필요 없어서 주석처리
                        <a class="nav-link {{ request()->routeIs('admin.second.round.qualifiers') ? 'active' : '' }}" 
                           href="{{ route('admin.second.round.qualifiers') }}">
                            <i class="bi bi-trophy"></i> 2차 예선 진출자
                        </a>
                        --}}
                        
                        <a class="nav-link" href="{{ route('admin.download.excel') }}">
                            <i class="bi bi-download"></i> 데이터 다운로드
                        </a>
                        @else
                        <!-- 심사위원 전용 메뉴 -->
                        <a class="nav-link {{ request()->routeIs('judge.dashboard') ? 'active' : '' }}" 
                           href="{{ route('judge.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> 심사위원 대시보드
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('judge.video.*') || request()->routeIs('judge.evaluation.*') ? 'active' : '' }}" 
                           href="{{ route('judge.video.list') }}">
                            <i class="bi bi-camera-video"></i> 배정된 영상 목록
                        </a>
                        @endif
                        
                        <hr class="text-white-50">
                        
                        <a class="nav-link" href="{{ url('/') }}" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> 대회 페이지
                        </a>
                        
                        <form action="{{ route('admin.logout') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm w-100">
                                <i class="bi bi-box-arrow-right"></i> 로그아웃
                            </button>
                        </form>
                    </nav>
        </div>
        
        <!-- 메인 콘텐츠 -->
        <div class="main-content">
            <!-- 모바일 메뉴 토글 버튼 -->
            <button class="btn btn-primary d-md-none mb-2" type="button" id="sidebarToggle">
                <i class="bi bi-list"></i> 메뉴
            </button>
            
            <!-- 알림 메시지 -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
            @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 사이드바 토글 스크립트 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
                
                // 사이드바 외부 클릭 시 닫기
                document.addEventListener('click', function(event) {
                    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>