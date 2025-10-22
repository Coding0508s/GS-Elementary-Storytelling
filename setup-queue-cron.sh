#!/bin/bash

# Queue Worker 자동 실행 설정 스크립트

echo "Queue Worker 자동 실행 설정을 시작합니다..."

# 현재 crontab 백업
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || echo "기존 crontab이 없습니다."

# 새로운 cron job 추가
(crontab -l 2>/dev/null; echo "# Laravel Queue Worker 자동 시작") | crontab -
(crontab -l 2>/dev/null; echo "@reboot cd /var/www/html/storytelling && php artisan queue:work --daemon > /dev/null 2>&1 &") | crontab -

# 매분마다 Queue Worker가 실행 중인지 확인하고 없으면 재시작
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/html/storytelling && pgrep -f 'queue:work' > /dev/null || nohup php artisan queue:work --daemon > /dev/null 2>&1 &") | crontab -

echo "✅ Crontab 설정 완료!"
echo "현재 crontab 내용:"
crontab -l

echo ""
echo "📋 설정된 작업:"
echo "1. 서버 재시작 시 Queue Worker 자동 시작"
echo "2. 매분마다 Queue Worker 상태 확인 및 재시작"
echo ""
echo "🚀 이제 서버 재시작 후에도 SMS가 자동으로 발송됩니다!"
