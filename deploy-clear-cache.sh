#!/bin/bash

# Laravel 캐시 클리어 스크립트
# 배포 후 라우트, 설정, 뷰 캐시를 모두 클리어합니다

echo "======================================"
echo "Laravel 캐시 클리어 시작"
echo "======================================"

# 프로젝트 디렉토리로 이동
cd /var/www/storytelling

echo ""
echo "1. 라우트 캐시 클리어..."
php artisan route:clear

echo ""
echo "2. 설정 캐시 클리어..."
php artisan config:clear

echo ""
echo "3. 뷰 캐시 클리어..."
php artisan view:clear

echo ""
echo "4. 애플리케이션 캐시 클리어..."
php artisan cache:clear

echo ""
echo "5. 최적화 캐시 클리어..."
php artisan optimize:clear

echo ""
echo "======================================"
echo "✅ 모든 캐시 클리어 완료!"
echo "======================================"

echo ""
echo "6. 라우트 목록 확인 (batch-ai-evaluation 관련)..."
php artisan route:list | grep "batch-ai-evaluation"

echo ""
echo "======================================"
echo "완료!"
echo "======================================"

