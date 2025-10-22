#!/bin/bash

# Contabo 서버 자동 배포 스크립트
# 사용법: ./deploy-contabo.sh [서버IP] [프로젝트경로]

set -e  # 오류 발생 시 스크립트 중단

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 기본값 설정
SERVER_IP=${1:-"your-contabo-server-ip"}
PROJECT_PATH=${2:-"/var/www/storytelling"}

echo -e "${BLUE}🚀 Contabo 서버 배포 시작${NC}"
echo -e "${YELLOW}서버: ${SERVER_IP}${NC}"
echo -e "${YELLOW}프로젝트 경로: ${PROJECT_PATH}${NC}"
echo ""

# 1. 서버 접속 및 배포 실행
echo -e "${BLUE}📡 서버에 접속하여 배포 실행 중...${NC}"
ssh root@${SERVER_IP} << EOF
    set -e
    
    echo -e "${GREEN}✅ 서버 접속 완료${NC}"
    
    # 프로젝트 디렉토리로 이동
    cd ${PROJECT_PATH}
    echo -e "${GREEN}✅ 프로젝트 디렉토리 이동: ${PROJECT_PATH}${NC}"
    
    # 현재 상태 확인
    echo -e "${BLUE}📊 현재 Git 상태 확인${NC}"
    git status
    echo ""
    
    # 현재 커밋 확인
    echo -e "${BLUE}📝 현재 커밋 정보${NC}"
    git log --oneline -3
    echo ""
    
    # 원격 저장소에서 최신 변경사항 가져오기
    echo -e "${BLUE}📥 원격 저장소에서 최신 변경사항 가져오기${NC}"
    git fetch origin
    echo ""
    
    # 변경사항 확인
    echo -e "${BLUE}🔄 변경사항 확인${NC}"
    git log HEAD..origin/master --oneline
    echo ""
    
    # 최신 변경사항 병합
    echo -e "${BLUE}🔄 최신 변경사항 병합${NC}"
    git pull origin master
    echo ""
    
    # Composer 의존성 업데이트
    echo -e "${BLUE}📦 Composer 의존성 업데이트${NC}"
    composer install --no-dev --optimize-autoloader
    echo ""
    
    # Laravel 캐시 클리어
    echo -e "${BLUE}🧹 Laravel 캐시 클리어${NC}"
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    echo ""
    
    # 데이터베이스 마이그레이션 (필요시)
    echo -e "${BLUE}🗄️ 데이터베이스 마이그레이션 확인${NC}"
    php artisan migrate --force
    echo ""
    
    # 권한 설정
    echo -e "${BLUE}🔐 파일 권한 설정${NC}"
    chown -R www-data:www-data ${PROJECT_PATH}
    chmod -R 755 ${PROJECT_PATH}
    chmod -R 775 ${PROJECT_PATH}/storage
    chmod -R 775 ${PROJECT_PATH}/bootstrap/cache
    echo ""
    
    # 웹서버 재시작
    echo -e "${BLUE}🔄 웹서버 재시작${NC}"
    systemctl restart nginx
    systemctl restart php8.1-fpm
    echo ""
    
    # 상태 확인
    echo -e "${BLUE}📊 서비스 상태 확인${NC}"
    systemctl status nginx --no-pager -l
    systemctl status php8.1-fpm --no-pager -l
    echo ""
    
    # 최종 커밋 확인
    echo -e "${GREEN}✅ 배포 완료! 최종 커밋 정보${NC}"
    git log --oneline -3
    echo ""
    
    echo -e "${GREEN}🎉 배포가 성공적으로 완료되었습니다!${NC}"
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}🎉 Contabo 서버 배포 완료!${NC}"
else
    echo -e "${RED}❌ 배포 중 오류가 발생했습니다.${NC}"
    exit 1
fi
