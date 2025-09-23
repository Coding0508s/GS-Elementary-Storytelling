#!/bin/bash

# ========================================
# Contabo 서버 배포 스크립트 - Speak and Shine 2025
# ========================================

set -e

echo "🚀 Contabo 서버에 Speak and Shine 2025 배포를 시작합니다..."

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 함수들
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# 시스템 업데이트
update_system() {
    log_info "시스템을 업데이트하는 중..."
    apt update && apt upgrade -y
    apt install -y curl wget git unzip software-properties-common
    log_success "시스템 업데이트가 완료되었습니다."
}

# Nginx 설치
install_nginx() {
    log_info "Nginx를 설치하는 중..."
    apt install -y nginx
    systemctl start nginx
    systemctl enable nginx
    ufw allow 'Nginx Full'
    log_success "Nginx가 설치되었습니다."
}

# MySQL 설치
install_mysql() {
    log_info "MySQL을 설치하는 중..."
    apt install -y mysql-server
    
    log_warning "MySQL 보안 설정을 실행하세요:"
    echo "mysql_secure_installation"
    
    log_success "MySQL이 설치되었습니다."
}

# PHP 설치
install_php() {
    log_info "PHP 8.2를 설치하는 중..."
    
    # PHP 8.2 저장소 추가
    add-apt-repository ppa:ondrej/php -y
    apt update
    
    # PHP 및 필요 확장 설치
    apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
        php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-redis \
        php8.2-intl php8.2-soap php8.2-cli
    
    log_success "PHP 8.2가 설치되었습니다."
}

# PHP 설정 수정
configure_php() {
    log_info "PHP 설정을 수정하는 중..."
    
    PHP_INI="/etc/php/8.2/fpm/php.ini"
    
    # 백업 생성
    cp $PHP_INI $PHP_INI.backup
    
    # 설정 수정
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 2048M/' $PHP_INI
    sed -i 's/post_max_size = .*/post_max_size = 2048M/' $PHP_INI
    sed -i 's/max_execution_time = .*/max_execution_time = 0/' $PHP_INI
    sed -i 's/max_input_time = .*/max_input_time = 3600/' $PHP_INI
    sed -i 's/memory_limit = .*/memory_limit = 2048M/' $PHP_INI
    
    # Opcache 설정
    echo "opcache.enable=1" >> $PHP_INI
    echo "opcache.memory_consumption=256" >> $PHP_INI
    
    systemctl restart php8.2-fpm
    log_success "PHP 설정이 완료되었습니다."
}

# Composer 설치
install_composer() {
    log_info "Composer를 설치하는 중..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    log_success "Composer가 설치되었습니다."
}

# 데이터베이스 설정
setup_database() {
    log_info "데이터베이스를 설정하는 중..."
    
    echo "MySQL에 접속하여 다음 명령어를 실행하세요:"
    echo "CREATE DATABASE storytelling_contest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "CREATE USER 'storytelling_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';"
    echo "GRANT ALL PRIVILEGES ON storytelling_contest.* TO 'storytelling_user'@'localhost';"
    echo "FLUSH PRIVILEGES;"
    echo "EXIT;"
    
    read -p "데이터베이스 설정이 완료되었으면 Enter를 누르세요..."
}

# 프로젝트 디렉토리 설정
setup_project_directory() {
    log_info "프로젝트 디렉토리를 설정하는 중..."
    
    mkdir -p /var/www/storytelling
    cd /var/www/storytelling
    
    log_success "프로젝트 디렉토리가 생성되었습니다: /var/www/storytelling"
}

# 권한 설정
set_permissions() {
    log_info "파일 권한을 설정하는 중..."
    
    chown -R www-data:www-data /var/www/storytelling
    chmod -R 755 /var/www/storytelling
    chmod -R 775 /var/www/storytelling/storage
    chmod -R 775 /var/www/storytelling/bootstrap/cache
    
    log_success "권한 설정이 완료되었습니다."
}

# Nginx 사이트 설정
configure_nginx() {
    log_info "Nginx를 설정하는 중..."
    
    cat > /etc/nginx/sites-available/storytelling << 'EOF'
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
        add_header Accept-Ranges bytes;
    }
}
EOF

    # 사이트 활성화
    ln -sf /etc/nginx/sites-available/storytelling /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # 설정 테스트
    nginx -t
    systemctl restart nginx
    
    log_success "Nginx 설정이 완료되었습니다."
}

# SSL 인증서 설치
install_ssl() {
    log_info "SSL 인증서를 설치하는 중..."
    
    apt install -y certbot python3-certbot-nginx
    
    log_warning "도메인을 설정한 후 다음 명령어를 실행하세요:"
    echo "certbot --nginx -d your-domain.com -d www.your-domain.com"
    
    # 자동 갱신 설정
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
    
    log_success "SSL 설정이 준비되었습니다."
}

# 방화벽 설정
setup_firewall() {
    log_info "방화벽을 설정하는 중..."
    
    ufw --force enable
    ufw allow OpenSSH
    ufw allow 'Nginx Full'
    ufw allow 80
    ufw allow 443
    ufw default deny incoming
    ufw default allow outgoing
    
    log_success "방화벽 설정이 완료되었습니다."
}

# 백업 스크립트 생성
create_backup_script() {
    log_info "백업 스크립트를 생성하는 중..."
    
    cat > /usr/local/bin/backup_storytelling_contabo.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/storytelling"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 데이터베이스 백업
mysqldump -u storytelling_user -p storytelling_contest > $BACKUP_DIR/db_$DATE.sql

# 업로드된 파일 백업
tar -czf $BACKUP_DIR/videos_$DATE.tar.gz /var/www/storytelling/storage/app/public/videos

# 오래된 백업 삭제 (30일 이상)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "백업 완료: $DATE"
EOF

    chmod +x /usr/local/bin/backup_storytelling_contabo.sh
    
    # 크론탭 설정 (매일 새벽 3시)
    (crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/backup_storytelling_contabo.sh >> /var/log/backup.log 2>&1") | crontab -
    
    log_success "백업 스크립트가 생성되었습니다."
}

# Laravel 애플리케이션 설정
setup_laravel() {
    log_info "Laravel 애플리케이션을 설정하는 중..."
    
    cd /var/www/storytelling
    
    # .env 파일 생성
    if [ ! -f ".env" ]; then
        cp .env.example .env
        log_warning ".env 파일을 수정하세요:"
        echo "nano /var/www/storytelling/.env"
    fi
    
    # Composer 설치
    composer install --optimize-autoloader --no-dev
    
    # Laravel 키 생성
    php artisan key:generate --force
    
    # 저장소 링크 생성
    php artisan storage:link
    
    # 마이그레이션 실행
    read -p "마이그레이션을 실행하시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan migrate --force
        php artisan db:seed --class=AdminSeeder
        php artisan db:seed --class=InstitutionSeeder
    fi
    
    # 캐시 최적화
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log_success "Laravel 애플리케이션 설정이 완료되었습니다."
}

# 시스템 모니터링 도구 설치
install_monitoring() {
    log_info "모니터링 도구를 설치하는 중..."
    
    apt install -y htop iotop nethogs
    
    log_success "모니터링 도구가 설치되었습니다."
}

# 메인 실행
main() {
    echo "========================================"
    echo "🎯 Contabo 서버 배포 스크립트"
    echo "🌟 Speak and Shine 2025"
    echo "========================================"
    echo
    
    # 시스템 업데이트
    update_system
    
    # 웹서버 스택 설치
    install_nginx
    install_mysql
    install_php
    configure_php
    install_composer
    
    # 데이터베이스 설정
    setup_database
    
    # 프로젝트 설정
    setup_project_directory
    
    echo
    log_warning "이제 다음 작업을 수동으로 진행하세요:"
    echo "1. 프로젝트 파일을 /var/www/storytelling에 업로드"
    echo "2. .env 파일 설정"
    echo "3. 도메인 설정"
    echo
    read -p "위 작업이 완료되었으면 Enter를 누르세요..."
    
    # 권한 및 설정
    set_permissions
    configure_nginx
    setup_laravel
    
    # 보안 및 백업
    setup_firewall
    install_ssl
    create_backup_script
    install_monitoring
    
    echo
    log_success "🎉 Contabo 서버 배포가 완료되었습니다!"
    echo
    echo "📋 다음 단계:"
    echo "  1. 도메인 DNS 설정"
    echo "  2. SSL 인증서 발급: certbot --nginx -d your-domain.com"
    echo "  3. .env 파일에서 도메인 및 비밀번호 설정"
    echo "  4. 웹사이트 접속 테스트"
    echo "  5. 파일 업로드 테스트"
    echo
    echo "📍 중요 파일 위치:"
    echo "  - 프로젝트: /var/www/storytelling"
    echo "  - Nginx 설정: /etc/nginx/sites-available/storytelling"
    echo "  - PHP 설정: /etc/php/8.2/fpm/php.ini"
    echo "  - 로그: /var/www/storytelling/storage/logs/laravel.log"
    echo
}

# 스크립트 실행
main "$@"
