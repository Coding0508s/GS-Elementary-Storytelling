#!/bin/bash

# ========================================
# 배포 환경 500 에러 수정 스크립트
# ========================================

set -e

echo "🔧 배포 환경 500 에러를 수정하는 중..."

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

# 1. 캐시 클리어
log_info "캐시를 클리어하는 중..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
log_success "캐시가 클리어되었습니다."

# 2. 마이그레이션 실행
log_info "마이그레이션을 실행하는 중..."
php artisan migrate --force
log_success "마이그레이션이 완료되었습니다."

# 3. site_settings 테이블 확인
log_info "site_settings 테이블을 확인하는 중..."
if php artisan tinker --execute="echo 'Site settings table exists: ' . (Schema::hasTable('site_settings') ? 'Yes' : 'No');" 2>/dev/null | grep -q "Yes"; then
    log_success "site_settings 테이블이 존재합니다."
else
    log_error "site_settings 테이블이 존재하지 않습니다!"
    exit 1
fi

# 4. site_settings 데이터 확인 및 생성
log_info "site_settings 데이터를 확인하는 중..."
if php artisan tinker --execute="echo 'Contest active setting: ' . (App\Models\SiteSetting::get('contest_active', 'NOT_FOUND'));" 2>/dev/null | grep -q "NOT_FOUND"; then
    log_info "contest_active 설정이 없습니다. 생성하는 중..."
    php artisan tinker --execute="App\Models\SiteSetting::set('contest_active', 'true', '대회 페이지 활성화 상태');"
    log_success "contest_active 설정이 생성되었습니다."
else
    log_success "contest_active 설정이 이미 존재합니다."
fi

# 5. 권한 설정
log_info "파일 권한을 설정하는 중..."
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
log_success "파일 권한이 설정되었습니다."

# 6. 프로덕션 최적화
log_info "프로덕션 최적화를 수행하는 중..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
log_success "프로덕션 최적화가 완료되었습니다."

# 7. 최종 확인
log_info "최종 확인을 수행하는 중..."
if php artisan route:list > /dev/null 2>&1; then
    log_success "라우트가 정상적으로 로드됩니다."
else
    log_error "라우트 로드에 실패했습니다!"
    exit 1
fi

echo
log_success "🎉 500 에러 수정이 완료되었습니다!"
echo
echo "📋 수정된 내용:"
echo "  1. 캐시 클리어 완료"
echo "  2. 마이그레이션 실행 완료"
echo "  3. site_settings 테이블 확인 완료"
echo "  4. contest_active 설정 생성 완료"
echo "  5. 파일 권한 설정 완료"
echo "  6. 프로덕션 최적화 완료"
echo
echo "이제 관리자 대시보드에 접속하여 대회 상태 토글을 테스트해보세요."
