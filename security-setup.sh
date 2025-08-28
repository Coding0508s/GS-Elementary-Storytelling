#!/bin/bash

# ========================================
# Speak and Shine 2025 보안 설정 스크립트
# ========================================

set -e

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}🔒 $1${NC}"
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

# 현재 디렉토리가 프로젝트 루트인지 확인
if [ ! -f "artisan" ]; then
    log_error "Laravel 프로젝트 루트 디렉토리에서 실행해주세요!"
    exit 1
fi

echo "========================================"
echo "🔒 Speak and Shine 2025 보안 설정"
echo "========================================"
echo

# 1. 파일 권한 설정
setup_file_permissions() {
    log_info "파일 권한을 설정하는 중..."
    
    # 기본 디렉토리 권한
    find . -type d -exec chmod 755 {} \;
    find . -type f -exec chmod 644 {} \;
    
    # 실행 파일 권한
    chmod +x artisan
    
    # 쓰기 가능한 디렉토리
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    # 민감한 파일 보호
    if [ -f ".env" ]; then
        chmod 600 .env
    fi
    
    log_success "파일 권한이 설정되었습니다."
}

# 2. 웹 서버 소유권 설정
setup_ownership() {
    log_info "파일 소유권을 설정하는 중..."
    
    # www-data 사용자 확인
    if id "www-data" &>/dev/null; then
        sudo chown -R www-data:www-data .
        sudo chown -R www-data:www-data storage bootstrap/cache
        log_success "www-data 소유권이 설정되었습니다."
    else
        log_warning "www-data 사용자가 없습니다. 수동으로 웹 서버 사용자 소유권을 설정하세요."
    fi
}

# 3. 민감한 디렉토리 보호
protect_directories() {
    log_info "민감한 디렉토리를 보호하는 중..."
    
    # .htaccess 파일 생성
    directories=("storage" "bootstrap/cache" "database" "config" "app")
    
    for dir in "${directories[@]}"; do
        if [ -d "$dir" ] && [ ! -f "$dir/.htaccess" ]; then
            echo "Deny from all" > "$dir/.htaccess"
            echo "Options -Indexes" >> "$dir/.htaccess"
        fi
    done
    
    # public 디렉토리의 민감한 파일 보호
    if [ -d "public" ] && [ ! -f "public/.htaccess" ]; then
        cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Angular HTML5 Mode
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^.*$ /index.php [L]

    # Deny access to sensitive files
    <FilesMatch "^\.">
        Order allow,deny
        Deny from all
    </FilesMatch>

    <FilesMatch "(composer\.(json|lock)|package\.(json|lock)|webpack\.mix\.js|artisan|\.env)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>

# PHP Upload Settings for Large Files (2GB)
php_value upload_max_filesize 2048M
php_value post_max_size 2048M
php_value max_execution_time 0
php_value max_input_time 3600
php_value memory_limit 2048M

# Enhanced error handling
php_value ignore_user_abort 1
php_value log_errors_max_len 0
php_value display_errors 0
php_value display_startup_errors 0
php_value error_reporting "E_ERROR | E_PARSE"

# Additional settings
php_value output_buffering 4096
php_value implicit_flush 0
EOF
    fi
    
    log_success "민감한 디렉토리가 보호되었습니다."
}

# 4. 로그 디렉토리 설정
setup_logging() {
    log_info "로그 디렉토리를 설정하는 중..."
    
    # 로그 디렉토리 생성
    mkdir -p storage/logs
    chmod 775 storage/logs
    
    # 로그 파일 초기화
    touch storage/logs/laravel.log
    chmod 664 storage/logs/laravel.log
    
    if id "www-data" &>/dev/null; then
        sudo chown www-data:www-data storage/logs/laravel.log
    fi
    
    log_success "로그 시스템이 설정되었습니다."
}

# 5. 세션 보안 설정
setup_session_security() {
    log_info "세션 보안을 설정하는 중..."
    
    # 세션 디렉토리 생성 (필요한 경우)
    if [ ! -d "/var/lib/php/sessions" ]; then
        sudo mkdir -p /var/lib/php/sessions
        sudo chmod 733 /var/lib/php/sessions
        sudo chown root:www-data /var/lib/php/sessions
    fi
    
    log_success "세션 보안이 설정되었습니다."
}

# 6. 임시 파일 정리
cleanup_temp_files() {
    log_info "임시 파일을 정리하는 중..."
    
    # 임시 디렉토리 생성
    mkdir -p storage/app/temp
    chmod 775 storage/app/temp
    
    # 오래된 임시 파일 삭제 (7일 이상)
    find storage/app/temp -type f -mtime +7 -delete 2>/dev/null || true
    
    log_success "임시 파일이 정리되었습니다."
}

# 7. 방화벽 설정 확인
check_firewall() {
    log_info "방화벽 설정을 확인하는 중..."
    
    if command -v ufw &> /dev/null; then
        if ufw status | grep -q "Status: active"; then
            log_success "UFW 방화벽이 활성화되어 있습니다."
        else
            log_warning "UFW 방화벽이 비활성화되어 있습니다. 활성화를 권장합니다."
            echo "sudo ufw enable"
            echo "sudo ufw allow 'Nginx Full'"
            echo "sudo ufw allow OpenSSH"
        fi
    else
        log_warning "UFW가 설치되지 않았습니다. 설치를 권장합니다."
        echo "sudo apt install ufw"
    fi
}

# 8. SSL/TLS 설정 확인
check_ssl() {
    log_info "SSL/TLS 설정을 확인하는 중..."
    
    if command -v certbot &> /dev/null; then
        log_success "Certbot이 설치되어 있습니다."
    else
        log_warning "Certbot이 설치되지 않았습니다. HTTPS를 위해 설치를 권장합니다."
        echo "sudo apt install certbot python3-certbot-nginx"
    fi
}

# 9. 데이터베이스 보안 확인
check_database_security() {
    log_info "데이터베이스 보안을 확인하는 중..."
    
    if [ -f ".env" ]; then
        # 기본 비밀번호 확인
        if grep -q "DB_PASSWORD=$" .env || grep -q "DB_PASSWORD=password" .env; then
            log_error "데이터베이스 비밀번호가 설정되지 않았거나 기본값입니다!"
            echo "강력한 비밀번호로 변경하세요."
        else
            log_success "데이터베이스 비밀번호가 설정되었습니다."
        fi
    else
        log_error ".env 파일이 없습니다!"
    fi
}

# 10. 백업 스크립트 생성
create_backup_script() {
    log_info "백업 스크립트를 생성하는 중..."
    
    cat > backup.sh << 'EOF'
#!/bin/bash

# Speak and Shine 2025 백업 스크립트

BACKUP_DIR="/var/backups/storytelling"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_DIR=$(pwd)

echo "🔄 백업을 시작합니다..."

# 백업 디렉토리 생성
mkdir -p $BACKUP_DIR

# 데이터베이스 백업
if [ -f ".env" ]; then
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    if [ ! -z "$DB_NAME" ] && [ ! -z "$DB_USER" ]; then
        mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/database_$DATE.sql
        echo "✅ 데이터베이스가 백업되었습니다."
    fi
fi

# 업로드된 파일 백업
if [ -d "storage/app/public/videos" ]; then
    tar -czf $BACKUP_DIR/videos_$DATE.tar.gz storage/app/public/videos
    echo "✅ 업로드된 파일이 백업되었습니다."
fi

# 설정 파일 백업
tar -czf $BACKUP_DIR/config_$DATE.tar.gz .env composer.json composer.lock

# 오래된 백업 삭제 (30일 이상)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "✅ 백업이 완료되었습니다: $BACKUP_DIR"
EOF

    chmod +x backup.sh
    log_success "백업 스크립트가 생성되었습니다."
}

# 메인 실행 함수
main() {
    echo "보안 설정을 시작합니다..."
    echo
    
    setup_file_permissions
    setup_ownership
    protect_directories
    setup_logging
    setup_session_security
    cleanup_temp_files
    check_firewall
    check_ssl
    check_database_security
    create_backup_script
    
    echo
    log_success "🎉 보안 설정이 완료되었습니다!"
    echo
    echo "📋 추가 권장사항:"
    echo "  1. 정기적으로 시스템 업데이트 실행"
    echo "  2. 강력한 관리자 비밀번호 설정"
    echo "  3. 정기적인 백업 실행 (./backup.sh)"
    echo "  4. 로그 모니터링 (storage/logs/laravel.log)"
    echo "  5. SSL 인증서 자동 갱신 설정"
    echo
}

# 스크립트 실행
main "$@"
