#!/bin/bash

# ========================================
# 서버 업데이트 스크립트 - 변경사항 반영
# ========================================

set -e

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

# 서버 정보 입력
read -p "서버 IP 주소를 입력하세요: " SERVER_IP
read -p "SSH 사용자명 (기본: root): " SSH_USER
SSH_USER=${SSH_USER:-root}

echo "🚀 서버 업데이트를 시작합니다..."
echo "📍 서버: $SSH_USER@$SERVER_IP"
echo

# SSH로 서버 업데이트 실행
ssh $SSH_USER@$SERVER_IP << 'ENDSSH'
#!/bin/bash

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

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 1. 프로젝트 디렉토리로 이동
log_info "프로젝트 디렉토리로 이동 중..."
cd /var/www/storytelling

# 2. Git 상태 확인
log_info "현재 Git 상태 확인 중..."
git status
echo

# 3. 최신 변경사항 가져오기
log_info "최신 변경사항을 가져오는 중..."
git fetch origin
git checkout deployment-setup
git pull origin deployment-setup
log_success "코드 업데이트 완료"

# 4. Composer 의존성 업데이트
log_info "Composer 의존성을 업데이트하는 중..."
composer install --no-dev --optimize-autoloader
log_success "Composer 업데이트 완료"

# 5. 데이터베이스 마이그레이션
log_info "데이터베이스 마이그레이션 실행 중..."
php artisan migrate --force
log_success "데이터베이스 마이그레이션 완료"

# 6. FFmpeg 설치 확인
log_info "FFmpeg 설치 상태 확인 중..."
if ! command -v ffmpeg &> /dev/null; then
    log_warning "FFmpeg를 설치하는 중..."
    apt update
    apt install -y ffmpeg
    log_success "FFmpeg 설치 완료"
else
    log_success "FFmpeg가 이미 설치되어 있습니다"
fi

# 7. 환경 설정 확인
log_info "환경 설정 확인 중..."
if ! grep -q "OPENAI_API_KEY" .env; then
    log_warning ".env 파일에 OPENAI_API_KEY를 추가해주세요"
    echo "OPENAI_API_KEY=your_openai_api_key_here" >> .env
    log_info "OPENAI_API_KEY 설정을 추가했습니다. 실제 키로 수정해주세요."
fi

# 8. 캐시 정리
log_info "캐시를 정리하는 중..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
log_success "캐시 정리 완료"

# 9. 최적화
log_info "프로덕션 최적화 적용 중..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
log_success "최적화 완료"

# 10. 권한 설정
log_info "파일 권한을 설정하는 중..."
chown -R www-data:www-data /var/www/storytelling
chmod -R 755 /var/www/storytelling
chmod -R 775 /var/www/storytelling/storage
chmod -R 775 /var/www/storytelling/bootstrap/cache
log_success "권한 설정 완료"

# 11. 웹 서버 재시작
log_info "웹 서버를 재시작하는 중..."
systemctl reload nginx
systemctl restart php8.2-fpm
log_success "웹 서버 재시작 완료"

# 12. 서비스 상태 확인
log_info "서비스 상태 확인 중..."
systemctl status nginx --no-pager -l
systemctl status php8.2-fpm --no-pager -l

echo
echo "🎉 서버 업데이트가 완료되었습니다!"
echo
echo "📋 업데이트된 기능들:"
echo "  ✅ AI 평가 시스템 (OpenAI Whisper + GPT-4)"
echo "  ✅ Excel(.xlsx) 다운로드 기능"
echo "  ✅ 새로운 평가 기준 (7개 항목, 70점 만점)"
echo "  ✅ 대용량 파일 처리 (최대 2GB)"
echo "  ✅ 심사위원 페이지 AI 평가 버튼"
echo "  ✅ 관리자 AI 평가 관리 페이지"
echo
echo "🔧 다음 단계:"
echo "  1. .env 파일에서 OPENAI_API_KEY를 실제 키로 수정"
echo "  2. 웹사이트 접속하여 새 기능 테스트"
echo "  3. AI 평가 기능 테스트 (관리자 > AI 설정에서 API 키 설정)"
echo
echo "📍 새로운 페이지들:"
echo "  - http://your-domain.com/admin/ai-evaluations (AI 평가 결과 관리)"
echo "  - http://your-domain.com/admin/ai-settings (AI 설정 관리)"
echo "  - 심사위원 페이지에서 AI 평가 버튼 확인"
echo

ENDSSH

if [ $? -eq 0 ]; then
    log_success "서버 업데이트가 성공적으로 완료되었습니다!"
    echo
    log_warning "중요: .env 파일에 실제 OpenAI API 키를 설정하세요:"
    echo "ssh $SSH_USER@$SERVER_IP"
    echo "nano /var/www/storytelling/.env"
    echo "# OPENAI_API_KEY=your_actual_api_key_here"
else
    log_error "서버 업데이트 중 오류가 발생했습니다."
    exit 1
fi
