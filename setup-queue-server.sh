#!/bin/bash

# ========================================
# Laravel Queue Worker 설정 스크립트 (서버용)
# ========================================

set -e

echo "🚀 Laravel Queue Worker를 설정합니다..."

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Supervisor 설치
install_supervisor() {
    log_info "Supervisor를 설치하는 중..."
    
    sudo apt update
    sudo apt install -y supervisor
    
    log_success "Supervisor가 설치되었습니다."
}

# Supervisor 설정 파일 생성
create_supervisor_config() {
    log_info "Supervisor 설정 파일을 생성하는 중..."
    
    sudo tee /etc/supervisor/conf.d/laravel-queue-worker.conf > /dev/null <<'EOF'
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --verbose --tries=3 --timeout=600 --sleep=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF
    
    log_success "Supervisor 설정 파일이 생성되었습니다."
}

# Supervisor 재시작
restart_supervisor() {
    log_info "Supervisor를 재시작하는 중..."
    
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start laravel-queue-worker:*
    
    log_success "Supervisor가 재시작되었습니다."
}

# 큐 워커 상태 확인
check_queue_status() {
    log_info "큐 워커 상태를 확인하는 중..."
    
    sudo supervisorctl status laravel-queue-worker:*
}

# 메인 실행
main() {
    echo "========================================"
    echo "🎯 Laravel Queue Worker 설정"
    echo "========================================"
    echo
    
    # Supervisor 설치
    install_supervisor
    
    # 설정 파일 생성
    create_supervisor_config
    
    # Supervisor 재시작
    restart_supervisor
    
    echo
    log_success "🎉 큐 워커 설정이 완료되었습니다!"
    echo
    
    # 상태 확인
    check_queue_status
    
    echo
    echo "📋 유용한 명령어:"
    echo "  - 큐 워커 상태 확인: sudo supervisorctl status laravel-queue-worker:*"
    echo "  - 큐 워커 시작: sudo supervisorctl start laravel-queue-worker:*"
    echo "  - 큐 워커 중지: sudo supervisorctl stop laravel-queue-worker:*"
    echo "  - 큐 워커 재시작: sudo supervisorctl restart laravel-queue-worker:*"
    echo "  - 로그 확인: tail -f /var/www/html/storage/logs/queue-worker.log"
    echo
}

# 스크립트 실행
main "$@"

