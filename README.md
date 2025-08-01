# GS Elementary Speech Contest

우리 회사의 교재를 사용하는 기관에 다니는 학생들을 대상으로 하는 Speech Contest 대회 시스템입니다.

## 📋 프로젝트 개요

이 시스템은 초등학생들이 자신이 배우고 있는 Unit에 대한 영어 발표 동영상을 업로드하고 관리할 수 있는 웹 애플리케이션입니다.

### 주요 기능

- ✅ **개인정보 수집 동의 관리**: 비디오 업로드 전 필수 동의 절차
- ✅ **학생 정보 입력**: 거주지역, 기관명, 반이름, 학생정보, 학부모 정보 등
- ✅ **비디오 파일 업로드**: 최대 2GB, MP4/MOV 형식 지원
- ✅ **Supabase 데이터베이스 연동**: 안전한 클라우드 데이터 저장
- ✅ **자동 알림 발송**: 업로드 완료 시 SMS 알림
- ✅ **반응형 웹 디자인**: 모바일/태블릿/데스크톱 지원

## 🛠 기술 스택

- **Backend**: PHP 8.x, Laravel 12.x
- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3
- **Database**: Supabase (PostgreSQL)
- **Storage**: Laravel Storage (Local/Cloud)
- **Notification**: SMS API 연동 준비
- **Server**: Apache/Nginx (XAMPP 지원)

## 📦 설치 및 설정

### 1. 시스템 요구사항

- PHP 8.1 이상
- Composer
- Node.js & NPM (선택사항)
- Apache/Nginx 웹서버
- Supabase 계정

### 2. 프로젝트 설치

```bash
# 1. 저장소 클론 (또는 프로젝트 디렉토리로 이동)
cd speech-contest

# 2. 의존성 설치
composer install

# 3. 환경 설정 파일 복사
cp .env.example .env

# 4. 애플리케이션 키 생성
php artisan key:generate

# 5. 스토리지 링크 생성
php artisan storage:link

# 6. 데이터베이스 마이그레이션
php artisan migrate
```

### 3. 환경 설정

`.env` 파일을 편집하여 다음 설정을 입력하세요:

```env
# 애플리케이션 설정
APP_NAME="GS Elementary Speech Contest"
APP_URL=http://localhost/speech-contest/public

# Supabase 설정
SUPABASE_URL=your_supabase_project_url
SUPABASE_ANON_KEY=your_supabase_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_supabase_service_role_key

# SMS 설정 (선택사항)
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret

# 파일 업로드 설정
MAX_UPLOAD_SIZE=2048
```

### 4. Supabase 데이터베이스 설정

Supabase 콘솔에서 다음 테이블을 생성하세요:

```sql
CREATE TABLE video_submissions (
    id BIGSERIAL PRIMARY KEY,
    region VARCHAR(255) NOT NULL,
    institution_name VARCHAR(255) NOT NULL,
    class_name VARCHAR(255) NOT NULL,
    student_name_korean VARCHAR(255) NOT NULL,
    student_name_english VARCHAR(255) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    age INTEGER NOT NULL,
    parent_name VARCHAR(255) NOT NULL,
    parent_phone VARCHAR(20) NOT NULL,
    video_file_path VARCHAR(255) NOT NULL,
    video_file_name VARCHAR(255) NOT NULL,
    video_file_type VARCHAR(10) NOT NULL,
    video_file_size BIGINT NOT NULL,
    unit_topic VARCHAR(255),
    privacy_consent BOOLEAN DEFAULT FALSE,
    privacy_consent_at TIMESTAMP,
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'uploaded',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### 5. 웹서버 설정

#### Apache (.htaccess 이미 포함됨)
```apache
# PHP 업로드 설정이 public/.htaccess에 포함되어 있습니다
php_value upload_max_filesize 2048M
php_value post_max_size 2048M
php_value max_execution_time 3600
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/speech-contest/public;
    index index.php;

    client_max_body_size 2048M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🚀 사용 방법

### 1. 시스템 접속
웹브라우저에서 `http://localhost/speech-contest/public` 또는 설정한 도메인으로 접속

### 2. 업로드 프로세스
1. **개인정보 동의**: 개인정보 수집 및 이용에 동의
2. **정보 입력**: 학생 및 학부모 정보 입력
3. **비디오 업로드**: MP4/MOV 파일 선택 및 업로드
4. **완료 확인**: 업로드 완료 및 알림 발송

### 3. 업로드 제한사항
- **파일 형식**: MP4, MOV만 지원
- **파일 크기**: 최대 2GB
- **필수 정보**: 모든 학생 및 학부모 정보 입력 필수

## 📱 알림 기능

### SMS 알림
- 업로드 완료 시 학부모 휴대폰으로 자동 발송
- 실제 운영시 SMS API 연동 필요 (현재는 로그로 기록)

### 지원 SMS 서비스
- NHN Cloud SMS
- 카카오 알림톡
- 네이버 클라우드 SMS
- 기타 SMS API 서비스

## 🔧 관리 기능

### 로그 확인
```bash
# 업로드 및 알림 로그 확인
tail -f storage/logs/laravel.log
```

### 데이터베이스 관리
- Supabase 콘솔을 통한 데이터 조회 및 관리
- Laravel Tinker를 통한 데이터 조작

```bash
php artisan tinker
>>> App\Models\VideoSubmission::all();
```

## 🔒 보안 고려사항

1. **개인정보 보호**: GDPR 및 개인정보보호법 준수
2. **파일 검증**: 업로드 파일 형식 및 크기 검증
3. **데이터 암호화**: Supabase를 통한 데이터 암호화 저장
4. **접근 제어**: 세션 기반 접근 제어
5. **로그 관리**: 개인정보 마스킹된 로그 기록

## 🛠 개발 및 커스터마이징

### 디렉토리 구조
```
speech-contest/
├── app/
│   ├── Http/Controllers/
│   │   └── VideoSubmissionController.php
│   ├── Models/
│   │   └── VideoSubmission.php
│   └── Services/
│       └── NotificationService.php
├── database/migrations/
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── privacy-consent.blade.php
│   ├── upload-form.blade.php
│   └── upload-success.blade.php
├── routes/
│   └── web.php
└── storage/app/public/videos/
```

### 주요 컴포넌트

1. **VideoSubmissionController**: 메인 컨트롤러
2. **VideoSubmission**: 데이터 모델
3. **NotificationService**: 알림 서비스
4. **Blade Templates**: 사용자 인터페이스

## 📞 지원 및 문의

### 기술 지원
- 개발팀 이메일: dev@gs-education.com
- 시스템 관리: admin@gs-education.com

### 개인정보 관련 문의
- 개인정보보호책임자: privacy@gs-education.com
- 전화: 02-1234-5678

## 📄 라이선스

이 프로젝트는 GS Education 전용 시스템입니다.

---

© 2024 GS Education. All rights reserved.
