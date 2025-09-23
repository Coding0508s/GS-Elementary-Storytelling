# 🚀 Contabo 서버 배포 가이드 - Speak and Shine 2025

## 📋 Contabo 서버 준비

### 1. 서버 스펙 권장사항
- **VPS S (최소)**: 4 vCPU, 8GB RAM, 200GB SSD
- **VPS M (권장)**: 6 vCPU, 16GB RAM, 400GB SSD  
- **OS**: Ubuntu 22.04 LTS (권장)
- **위치**: 독일 (기본) 또는 미국 동부/서부

### 2. 초기 서버 설정

#### SSH 접속
```bash
# Contabo 관리 패널에서 받은 정보로 접속
ssh root@your-server-ip
```

#### 시스템 업데이트
```bash
apt update && apt upgrade -y
apt install -y curl wget git unzip software-properties-common
```

## 🛠️ LAMP/LEMP 스택 설치

### 방법 1: Nginx + PHP-FPM (권장)

#### 1. Nginx 설치
```bash
apt install -y nginx
systemctl start nginx
systemctl enable nginx
ufw allow 'Nginx Full'
```

#### 2. MySQL 8.0 설치
```bash
# MySQL 8.0 설치
apt install -y mysql-server

# MySQL 보안 설정
mysql_secure_installation

# MySQL 설정
mysql -u root -p
```

#### 3. MySQL 데이터베이스 및 사용자 생성
```sql
CREATE DATABASE storytelling_contest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'storytelling_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON storytelling_contest.* TO 'storytelling_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 4. PHP 8.2 설치
```bash
# PHP 8.2 저장소 추가
add-apt-repository ppa:ondrej/php -y
apt update

# PHP 및 필요 확장 설치
apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-redis \
    php8.2-intl php8.2-soap php8.2-cli

# PHP 설정 수정 (대용량 파일 업로드용)
nano /etc/php/8.2/fpm/php.ini
```

#### 5. PHP 설정 수정 (php.ini)
```ini
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 0
max_input_time = 3600
memory_limit = 2048M
max_file_uploads = 50
```

#### 6. Composer 설치
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

## 📁 프로젝트 배포

### 1. 프로젝트 디렉토리 생성
```bash
mkdir -p /var/www/storytelling
cd /var/www
```

### 2. 프로젝트 파일 업로드

#### 방법 A: Git 사용 (권장)
```bash
git clone https://github.com/your-username/storytelling.git
cd storytelling
```

#### 방법 B: 직접 파일 업로드
```bash
# 로컬에서 서버로 파일 전송
scp -r /Applications/XAMPP/xamppfiles/htdocs/storytelling/* root@your-server-ip:/var/www/storytelling/
```

### 3. 권한 설정
```bash
chown -R www-data:www-data /var/www/storytelling
chmod -R 755 /var/www/storytelling
chmod -R 775 /var/www/storytelling/storage
chmod -R 775 /var/www/storytelling/bootstrap/cache
```

### 4. 환경 설정
```bash
cd /var/www/storytelling
cp .env.example .env
nano .env
```

#### .env 파일 설정 (Contabo용)
```env
APP_NAME="Speak and Shine 2025"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storytelling_contest
DB_USERNAME=storytelling_user
DB_PASSWORD=YOUR_SECURE_PASSWORD_HERE

# 파일 저장소 (로컬 사용)
FILESYSTEM_DISK=public

# 캐시 설정
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# 메일 설정 (필요시)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"

# Twilio SMS (실제 값으로 교체 필요)
TWILIO_ACCOUNT_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM_NUMBER=your_twilio_phone_number

# 업로드 설정
UPLOAD_MAX_FILESIZE=2048M
POST_MAX_SIZE=2048M
MAX_EXECUTION_TIME=0
MEMORY_LIMIT=2048M
```

### 5. Laravel 설정
```bash
# Composer 패키지 설치
composer install --optimize-autoloader --no-dev

# Laravel 키 생성
php artisan key:generate

# 저장소 링크 생성
php artisan storage:link
```

## 🗄️ 데이터베이스 배포

### 방법 1: 덤프 파일 사용 (기존 데이터 포함)
```bash
# 로컬에서 서버로 덤프 파일 전송
scp mysql_database_export_20250903_150647.sql root@your-server-ip:/var/www/storytelling/

# 서버에서 데이터베이스 복원
cd /var/www/storytelling
mysql -u storytelling_user -p storytelling_contest < mysql_database_export_20250903_150647.sql
```

### 방법 2: 마이그레이션 사용 (권장 - 프로덕션)
```bash
# 마이그레이션 실행
php artisan migrate --force

# 기본 데이터 시드
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=InstitutionSeeder
```

## 🌐 Nginx 설정

### 1. Nginx 사이트 설정 생성
```bash
nano /etc/nginx/sites-available/storytelling
```

### 2. Nginx 설정 파일 내용
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/storytelling/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # 대용량 파일 업로드 설정
    client_max_body_size 2048M;
    client_body_timeout 300s;
    proxy_read_timeout 300s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffering off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 정적 파일 캐싱
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 비디오 파일 설정
    location ~* \.(mp4|webm|ogg)$ {
        expires 1y;
        add_header Cache-Control "public";
        # 비디오 스트리밍 지원
        add_header Accept-Ranges bytes;
    }
}
```

### 3. 사이트 활성화
```bash
# 사이트 활성화
ln -s /etc/nginx/sites-available/storytelling /etc/nginx/sites-enabled/

# 기본 사이트 비활성화
rm /etc/nginx/sites-enabled/default

# 설정 테스트
nginx -t

# Nginx 재시작
systemctl restart nginx
systemctl restart php8.2-fpm
```

## 🔒 SSL 인증서 설정 (Let's Encrypt)

```bash
# Certbot 설치
apt install -y certbot python3-certbot-nginx

# SSL 인증서 발급
certbot --nginx -d your-domain.com -d www.your-domain.com

# 자동 갱신 설정
crontab -e
# 다음 라인 추가:
0 12 * * * /usr/bin/certbot renew --quiet
```

## 🚀 최종 배포 및 최적화

### 1. 배포 스크립트 실행
```bash
cd /var/www/storytelling
chmod +x deploy.sh
./deploy.sh
```

### 2. 추가 최적화
```bash
# 캐시 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Opcache 활성화 (php.ini)
echo "opcache.enable=1" >> /etc/php/8.2/fpm/php.ini
echo "opcache.memory_consumption=256" >> /etc/php/8.2/fpm/php.ini

systemctl restart php8.2-fpm
```

## 🔥 방화벽 설정

```bash
# UFW 방화벽 설정
ufw enable
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw allow 80
ufw allow 443

# 불필요한 포트 차단
ufw default deny incoming
ufw default allow outgoing
```

## 📊 모니터링 설정

### 1. 로그 위치
```bash
# Laravel 로그
tail -f /var/www/storytelling/storage/logs/laravel.log

# Nginx 로그
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# MySQL 로그
tail -f /var/log/mysql/error.log
```

### 2. 시스템 모니터링
```bash
# 시스템 리소스 모니터링
htop
df -h
free -h
```

## 🔄 백업 설정

### 자동 백업 스크립트 생성
```bash
nano /usr/local/bin/backup_storytelling_contabo.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/storytelling"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 데이터베이스 백업
mysqldump -u storytelling_user -p'YOUR_PASSWORD' storytelling_contest > $BACKUP_DIR/db_$DATE.sql

# 업로드된 파일 백업
tar -czf $BACKUP_DIR/videos_$DATE.tar.gz /var/www/storytelling/storage/app/public/videos

# 오래된 백업 삭제 (30일 이상)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "백업 완료: $DATE"
```

```bash
chmod +x /usr/local/bin/backup_storytelling_contabo.sh

# 크론탭 설정 (매일 새벽 3시)
crontab -e
0 3 * * * /usr/local/bin/backup_storytelling_contabo.sh >> /var/log/backup.log 2>&1
```

## ✅ 배포 완료 체크리스트

- [ ] Contabo 서버 준비 완료
- [ ] LEMP 스택 설치 완료
- [ ] MySQL 데이터베이스 생성 완료
- [ ] 프로젝트 파일 업로드 완료
- [ ] 환경 설정 (.env) 완료
- [ ] 데이터베이스 마이그레이션/복원 완료
- [ ] Nginx 설정 완료
- [ ] SSL 인증서 설치 완료
- [ ] 방화벽 설정 완료
- [ ] 웹사이트 접속 테스트 완료
- [ ] 파일 업로드 테스트 완료
- [ ] 관리자 로그인 테스트 완료
- [ ] 백업 시스템 설정 완료

## 🚨 Contabo 특화 주의사항

1. **네트워크 대역폭**: Contabo는 무제한 트래픽이지만 속도 제한이 있을 수 있습니다.
2. **백업**: Contabo 자체 백업 서비스 활용 권장
3. **보안**: 정기적인 보안 업데이트 필수
4. **모니터링**: Contabo 관리 패널에서 리소스 사용량 모니터링

---

이 가이드를 따라 Contabo 서버에 성공적으로 배포하실 수 있습니다!
