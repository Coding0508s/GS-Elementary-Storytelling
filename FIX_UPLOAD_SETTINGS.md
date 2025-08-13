# 2GB 업로드 오류 수정 가이드

## 🚨 현재 문제
- 업로드 시도한 파일: 1,187,041,523 bytes (약 1.1GB)
- 현재 PHP 제한: 1,073,741,824 bytes (1GB)
- 부족한 용량: 113,299,699 bytes (약 113MB)

## ✅ 해결 방법

### 방법 1: PHP 설정 파일 직접 수정 (권장)

1. **PHP 설정 파일 열기:**
```bash
sudo nano /opt/homebrew/etc/php/8.3/php.ini
```

2. **다음 설정값들을 찾아서 수정:**
```ini
; 변경 전
upload_max_filesize = 1024M
post_max_size = 1024M
memory_limit = 512M

; 변경 후
upload_max_filesize = 2048M
post_max_size = 2048M
memory_limit = 1024M
```

3. **저장 후 PHP 재시작:**
```bash
brew services restart php
```

4. **Laravel 서버 재시작:**
```bash
pkill -f "php artisan serve"
php artisan serve --host=0.0.0.0 --port=8000
```

### 방법 2: 임시 설정 (이미 적용됨)

다음 파일들이 이미 생성되었습니다:
- `public/.htaccess` - Apache 설정
- `public/.user.ini` - PHP 사용자 설정
- `public/index.php` - ini_set 설정

### 설정 확인

브라우저에서 다음 URL로 설정 확인:
```
http://localhost:8000/phpinfo.php
```

## 🎯 성공 기준

설정이 올바르게 적용되면:
- upload_max_filesize: 2048M
- post_max_size: 2048M  
- memory_limit: 1024M

## 📞 문제 해결

설정 후에도 문제가 지속되면:
1. 웹서버 재시작
2. 브라우저 캐시 클리어
3. 설정 확인: `php -i | grep -E "(post_max_size|upload_max_filesize)"`
