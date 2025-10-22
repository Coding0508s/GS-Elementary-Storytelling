# Contabo 서버 Git 설정 가이드

## 1. 서버 초기 설정

### SSH 키 설정
```bash
# 로컬에서 SSH 키 생성 (이미 생성됨)
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# 공개키를 서버에 복사
ssh-copy-id root@your-contabo-server-ip
```

### 서버에서 Git 설정
```bash
# 서버 접속
ssh root@your-contabo-server-ip

# Git 사용자 정보 설정
git config --global user.name "Contabo Server"
git config --global user.email "server@contabo.com"

# 프로젝트 디렉토리 생성
mkdir -p /var/www/storytelling
cd /var/www/storytelling

# GitHub 저장소 클론
git clone https://github.com/Coding0508s/GS-Elementary-Storytelling.git .

# 또는 SSH로 클론 (SSH 키 설정 후)
git clone git@github.com:Coding0508s/GS-Elementary-Storytelling.git .
```

## 2. 환경 설정

### .env 파일 설정
```bash
# .env 파일 생성
cp .env.example .env

# .env 파일 편집
nano .env
```

### 필수 환경 변수
```env
APP_NAME="Speak and Shine 2025"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storytelling_db
DB_USERNAME=storytelling_user
DB_PASSWORD=your-db-password

FILESYSTEM_DISK=s3
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=720

AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=ap-northeast-2
AWS_BUCKET=your-s3-bucket

TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_FROM_NUMBER=your-twilio-number
```

## 3. 의존성 설치

```bash
# Composer 설치
composer install --no-dev --optimize-autoloader

# Laravel 키 생성
php artisan key:generate

# 데이터베이스 마이그레이션
php artisan migrate --force

# 스토리지 링크 생성
php artisan storage:link

# 권한 설정
chown -R www-data:www-data /var/www/storytelling
chmod -R 755 /var/www/storytelling
chmod -R 775 /var/www/storytelling/storage
chmod -R 775 /var/www/storytelling/bootstrap/cache
```

## 4. 웹서버 설정

### Nginx 설정
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /var/www/storytelling/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### SSL 인증서 설정 (Let's Encrypt)
```bash
# Certbot 설치
apt install certbot python3-certbot-nginx

# SSL 인증서 발급
certbot --nginx -d your-domain.com

# 자동 갱신 설정
crontab -e
# 다음 라인 추가:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

## 5. 자동 배포 설정

### Cron 작업 설정
```bash
# Cron 편집
crontab -e

# 매일 새벽 2시에 자동 배포 (선택사항)
0 2 * * * cd /var/www/storytelling && git pull origin master && php artisan config:clear && php artisan cache:clear
```

### 배포 스크립트 사용
```bash
# 로컬에서 실행
./deploy-contabo.sh your-server-ip /var/www/storytelling
```

## 6. 모니터링 및 로그

### 로그 확인
```bash
# Laravel 로그
tail -f /var/www/storytelling/storage/logs/laravel.log

# Nginx 로그
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP-FPM 로그
tail -f /var/log/php8.1-fpm.log
```

### 시스템 모니터링
```bash
# 시스템 리소스 확인
htop
df -h
free -h

# 서비스 상태 확인
systemctl status nginx
systemctl status php8.1-fpm
systemctl status mysql
```

## 7. 백업 설정

### 데이터베이스 백업
```bash
# 백업 스크립트 생성
nano /root/backup-db.sh

#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u storytelling_user -p storytelling_db > /backup/storytelling_$DATE.sql
find /backup -name "storytelling_*.sql" -mtime +7 -delete

# 실행 권한 부여
chmod +x /root/backup-db.sh

# Cron에 추가 (매일 새벽 1시)
0 1 * * * /root/backup-db.sh
```

### 파일 백업
```bash
# 파일 백업 스크립트
nano /root/backup-files.sh

#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backup/storytelling_files_$DATE.tar.gz /var/www/storytelling
find /backup -name "storytelling_files_*.tar.gz" -mtime +7 -delete

chmod +x /root/backup-files.sh
```

## 8. 보안 설정

### 방화벽 설정
```bash
# UFW 방화벽 설정
ufw allow ssh
ufw allow 'Nginx Full'
ufw enable
```

### SSH 보안 강화
```bash
# SSH 설정 편집
nano /etc/ssh/sshd_config

# 다음 설정 추가/수정:
Port 2222
PermitRootLogin yes
PasswordAuthentication no
PubkeyAuthentication yes

# SSH 서비스 재시작
systemctl restart ssh
```

## 9. 성능 최적화

### PHP 설정 최적화
```bash
# PHP 설정 편집
nano /etc/php/8.1/fpm/php.ini

# 주요 설정:
memory_limit = 512M
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 300
max_input_time = 300
```

### MySQL 설정 최적화
```bash
# MySQL 설정 편집
nano /etc/mysql/mysql.conf.d/mysqld.cnf

# 주요 설정:
innodb_buffer_pool_size = 256M
max_connections = 200
query_cache_size = 32M
```

이제 Contabo 서버에서 Git을 사용한 자동 배포가 가능합니다!
