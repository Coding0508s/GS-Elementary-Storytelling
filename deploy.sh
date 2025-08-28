#!/bin/bash

# ========================================
# Speak and Shine 2025 배포 스크립트
# ========================================

set -e

echo "🚀 Speak and Shine 2025 배포를 시작합니다..."

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

# 환경 변수 확인
check_environment() {
    log_info "환경 변수를 확인하는 중..."
    
    if [ ! -f ".env" ]; then
        log_error ".env 파일이 존재하지 않습니다!"
        log_info ".env.production을 참고하여 .env 파일을 생성하세요."
        exit 1
    fi
    
    # 중요한 환경 변수 확인
    if grep -q "APP_KEY=base64:" .env; then
        log_success "APP_KEY가 설정되었습니다."
    else
        log_error "APP_KEY가 설정되지 않았습니다!"
        log_info "php artisan key:generate를 실행하세요."
        exit 1
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        log_success "프로덕션 모드가 활성화되었습니다."
    else
        log_warning "APP_DEBUG=false로 설정하는 것을 권장합니다."
    fi
}

# 의존성 설치
install_dependencies() {
    log_info "의존성을 설치하는 중..."
    
    # Composer 설치
    if command -v composer &> /dev/null; then
        composer install --optimize-autoloader --no-dev
        log_success "Composer 패키지가 설치되었습니다."
    else
        log_error "Composer가 설치되지 않았습니다!"
        exit 1
    fi
    
    # Node.js 패키지 설치 (있는 경우)
    if [ -f "package.json" ]; then
        if command -v npm &> /dev/null; then
            npm ci --only=production
            npm run build
            log_success "Node.js 패키지가 설치되고 빌드되었습니다."
        else
            log_warning "npm이 설치되지 않았습니다. 프론트엔드 빌드를 건너뜁니다."
        fi
    fi
}

# 데이터베이스 설정
setup_database() {
    log_info "데이터베이스를 설정하는 중..."
    
    # 마이그레이션 실행
    php artisan migrate --force
    log_success "데이터베이스 마이그레이션이 완료되었습니다."
    
    # 시더 실행 (프로덕션에서는 선택적)
    read -p "시더를 실행하시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan db:seed --class=AdminSeeder
        php artisan db:seed --class=InstitutionSeeder
        log_success "시더가 실행되었습니다."
    fi
}

# 권한 설정
set_permissions() {
    log_info "파일 권한을 설정하는 중..."
    
    # 디렉토리 권한 설정
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    
    # 웹 서버 사용자에게 소유권 부여 (예: www-data)
    if id "www-data" &>/dev/null; then
        sudo chown -R www-data:www-data storage bootstrap/cache
        log_success "웹 서버 권한이 설정되었습니다."
    else
        log_warning "www-data 사용자가 없습니다. 수동으로 웹 서버 권한을 설정하세요."
    fi
}

# 캐시 및 최적화
optimize_application() {
    log_info "애플리케이션을 최적화하는 중..."
    
    # 캐시 클리어
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # 프로덕션 최적화
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Composer 자동로더 최적화
    composer dump-autoload --optimize
    
    log_success "애플리케이션이 최적화되었습니다."
}

# 저장소 링크 생성
create_storage_link() {
    log_info "저장소 링크를 생성하는 중..."
    
    if [ ! -L "public/storage" ]; then
        php artisan storage:link
        log_success "저장소 링크가 생성되었습니다."
    else
        log_success "저장소 링크가 이미 존재합니다."
    fi
}

# 보안 검사
security_check() {
    log_info "보안 설정을 검사하는 중..."
    
    # .env 파일 권한 확인
    if [ -f ".env" ]; then
        chmod 600 .env
        log_success ".env 파일 권한이 설정되었습니다."
    fi
    
    # 민감한 디렉토리 보호
    if [ ! -f "storage/.htaccess" ]; then
        echo "Deny from all" > storage/.htaccess
    fi
    
    if [ ! -f "bootstrap/cache/.htaccess" ]; then
        echo "Deny from all" > bootstrap/cache/.htaccess
    fi
    
    log_success "보안 설정이 완료되었습니다."
}

# 배포 후 확인
post_deploy_check() {
    log_info "배포 후 확인을 수행하는 중..."
    
    # 라우트 확인
    if php artisan route:list > /dev/null 2>&1; then
        log_success "라우트가 정상적으로 로드됩니다."
    else
        log_error "라우트 로드에 실패했습니다!"
        exit 1
    fi
    
    # 데이터베이스 연결 확인
    if php artisan migrate:status > /dev/null 2>&1; then
        log_success "데이터베이스 연결이 정상입니다."
    else
        log_error "데이터베이스 연결에 실패했습니다!"
        exit 1
    fi
}

# 백업 생성
create_backup() {
    log_info "배포 전 백업을 생성하는 중..."
    
    BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # 데이터베이스 백업
    if command -v mysqldump &> /dev/null; then
        DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -1)
        if [ ! -z "$DB_NAME" ]; then
            mysqldump "$DB_NAME" > "$BACKUP_DIR/database.sql"
            log_success "데이터베이스가 백업되었습니다."
        fi
    fi
    
    # 업로드된 파일 백업
    if [ -d "storage/app/public/videos" ]; then
        cp -r storage/app/public/videos "$BACKUP_DIR/"
        log_success "업로드된 파일이 백업되었습니다."
    fi
}

# 메인 실행
main() {
    echo "========================================"
    echo "🎯 Speak and Shine 2025 배포 스크립트"
    echo "========================================"
    echo
    
    # 백업 생성
    create_backup
    
    # 환경 확인
    check_environment
    
    # 의존성 설치
    install_dependencies
    
    # 데이터베이스 설정
    setup_database
    
    # 저장소 링크 생성
    create_storage_link
    
    # 권한 설정
    set_permissions
    
    # 애플리케이션 최적화
    optimize_application
    
    # 보안 검사
    security_check
    
    # 배포 후 확인
    post_deploy_check
    
    echo
    log_success "🎉 배포가 성공적으로 완료되었습니다!"
    echo
    echo "📋 배포 후 체크리스트:"
    echo "  1. 웹사이트에 접속하여 정상 동작 확인"
    echo "  2. 업로드 기능 테스트"
    echo "  3. 관리자 로그인 테스트"
    echo "  4. SSL 인증서 확인"
    echo "  5. 도메인 설정 확인"
    echo
}

# 스크립트 실행
main "$@"
