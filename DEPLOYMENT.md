# 🚀 Speak and Shine 2025 배포 가이드

## 📋 배포 전 체크리스트

### 1. 시스템 요구사항
- **PHP**: 8.1 이상
- **MySQL**: 8.0 이상 또는 MariaDB 10.3 이상
- **Nginx**: 1.18 이상 또는 Apache 2.4 이상
- **Node.js**: 18 이상 (선택사항)
- **Redis**: 6.0 이상 (캐시용, 권장)
- **SSL 인증서**: HTTPS 필수

### 2. PHP 확장 모듈
```bash
# 필수 확장 모듈 설치
sudo apt-get install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis php8.2-bcmath
```

### 3. 서버 설정 요구사항
- **디스크 공간**: 최소 10GB (동영상 저장용 추가 공간 필요)
- **메모리**: 최소 2GB RAM
- **업로드 제한**: 2GB 파일 업로드 지원
- **실행 시간**: 무제한 (대용량 파일 처리용)

## 🔧 배포 과정

### 1단계: 프로젝트 업로드
```bash
# 서버에 프로젝트 업로드
cd /var/www/
sudo git clone https://github.com/your-repo/storytelling.git
sudo chown -R www-data:www-data storytelling
cd storytelling
```

### 2단계: 환경 설정
```bash
# 환경 설정 파일 생성
cp .env.example .env
nano .env

# 다음 값들을 수정하세요:
APP_NAME="Speak and Shine 2025"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storytelling_contest_prod
DB_USERNAME=storytelling_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

CACHE_STORE=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# 메일 설정 (Gmail 사용 시)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### 3단계: 보안 키 생성
```bash
php artisan key:generate
```

### 4단계: 데이터베이스 설정
```bash
# MySQL 접속
mysql -u root -p

# 데이터베이스 및 사용자 생성
CREATE DATABASE storytelling_contest_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'storytelling_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON storytelling_contest_prod.* TO 'storytelling_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 마이그레이션 실행
php artisan migrate --force

# 기본 데이터 입력
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=InstitutionSeeder
```

### 5단계: 의존성 설치 및 빌드
```bash
# Composer 의존성 설치
composer install --optimize-autoloader --no-dev

# Node.js 패키지 설치 및 빌드 (있는 경우)
npm ci --only=production
npm run build
```

### 6단계: 배포 스크립트 실행
```bash
# 실행 권한 부여
chmod +x deploy.sh

# 배포 스크립트 실행
./deploy.sh
```

### 7단계: Nginx 설정
```bash
# Nginx 설정 파일 복사
sudo cp nginx.conf /etc/nginx/sites-available/storytelling
sudo ln -s /etc/nginx/sites-available/storytelling /etc/nginx/sites-enabled/

# 기본 사이트 비활성화
sudo rm /etc/nginx/sites-enabled/default

# 설정 테스트 및 재시작
sudo nginx -t
sudo systemctl restart nginx
```

### 8단계: PHP-FPM 설정
```bash
# PHP-FPM 설정 파일 수정
sudo nano /etc/php/8.2/fpm/php.ini

# 다음 값들을 수정:
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 0
max_input_time = 3600
memory_limit = 2048M

# PHP-FPM 재시작
sudo systemctl restart php8.2-fpm
```

## 🔒 보안 설정

### 1. 파일 권한 설정
```bash
# 디렉토리 권한
sudo chmod -R 755 /var/www/storytelling
sudo chmod -R 775 /var/www/storytelling/storage
sudo chmod -R 775 /var/www/storytelling/bootstrap/cache

# 소유권 설정
sudo chown -R www-data:www-data /var/www/storytelling

# .env 파일 보안
sudo chmod 600 /var/www/storytelling/.env
```

### 2. 방화벽 설정
```bash
# UFW 방화벽 설정
sudo ufw enable
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
```

### 3. SSL 인증서 설정
```bash
# Let's Encrypt 인증서 설치
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 자동 갱신 설정
sudo crontab -e
# 다음 라인 추가:
0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 모니터링 및 유지보수

### 1. 로그 모니터링
```bash
# Laravel 로그
tail -f /var/www/storytelling/storage/logs/laravel.log

# Nginx 로그
tail -f /var/log/nginx/storytelling_access.log
tail -f /var/log/nginx/storytelling_error.log

# PHP-FPM 로그
tail -f /var/log/php8.2-fpm.log
```

### 2. 데이터베이스 백업
```bash
# 자동 백업 스크립트 생성
sudo nano /usr/local/bin/backup_storytelling.sh

#!/bin/bash
BACKUP_DIR="/var/backups/storytelling"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 데이터베이스 백업
mysqldump -u storytelling_user -p storytelling_contest_prod > $BACKUP_DIR/db_$DATE.sql

# 파일 백업
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/storytelling/storage/app/public/videos

# 오래된 백업 삭제 (30일 이상)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

# 실행 권한 부여
sudo chmod +x /usr/local/bin/backup_storytelling.sh

# 크론탭에 추가 (매일 새벽 2시)
sudo crontab -e
0 2 * * * /usr/local/bin/backup_storytelling.sh
```

### 3. 시스템 업데이트
```bash
# 정기 업데이트
sudo apt update && sudo apt upgrade -y

# Laravel 업데이트 (필요시)
composer update
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🚨 트러블슈팅

### 1. 업로드 오류
- **증상**: 대용량 파일 업로드 실패
- **해결**: PHP, Nginx 업로드 제한 설정 확인
- **명령어**: `php -i | grep upload_max_filesize`

### 2. 권한 오류
- **증상**: 500 Internal Server Error
- **해결**: 파일 권한 및 소유권 재설정
- **명령어**: `sudo chown -R www-data:www-data storage bootstrap/cache`

### 3. 데이터베이스 연결 오류
- **증상**: Database connection error
- **해결**: .env 파일의 데이터베이스 설정 확인
- **확인**: `php artisan config:clear && php artisan migrate:status`

### 4. 캐시 문제
- **증상**: 변경사항이 반영되지 않음
- **해결**: 모든 캐시 클리어
- **명령어**: 
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📞 지원 연락처

배포 관련 문제가 발생하면 다음 로그를 확인하고 필요시 기술 지원팀에 문의하세요:

1. **Laravel 로그**: `/var/www/storytelling/storage/logs/laravel.log`
2. **Nginx 에러 로그**: `/var/log/nginx/storytelling_error.log`
3. **PHP-FPM 로그**: `/var/log/php8.2-fpm.log`

---

## ✅ 배포 완료 체크리스트

- [ ] 프로젝트 업로드 완료
- [ ] 환경 설정 (.env) 완료
- [ ] 데이터베이스 설정 완료
- [ ] SSL 인증서 설치 완료
- [ ] Nginx 설정 완료
- [ ] 파일 권한 설정 완료
- [ ] 웹사이트 접속 테스트 완료
- [ ] 업로드 기능 테스트 완료
- [ ] 관리자 로그인 테스트 완료
- [ ] 심사 기능 테스트 완료
- [ ] 백업 시스템 설정 완료

배포가 완료되면 이 체크리스트를 확인하여 모든 기능이 정상 작동하는지 검증하세요.
