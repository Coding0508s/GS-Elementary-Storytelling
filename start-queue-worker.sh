#!/bin/bash

# Laravel Queue Worker 시작 스크립트
# AI 일괄 채점을 위한 백그라운드 작업 처리

echo "Laravel Queue Worker를 시작합니다..."
echo "AI 일괄 채점 작업을 처리합니다."
echo ""

# 프로젝트 디렉토리로 이동
cd "$(dirname "$0")"

# 큐 워커 시작 (백그라운드에서 실행)
nohup php artisan queue:work --verbose --tries=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &

# 프로세스 ID 저장
echo $! > storage/logs/queue-worker.pid

echo "Queue Worker가 시작되었습니다."
echo "PID: $(cat storage/logs/queue-worker.pid)"
echo "로그 파일: storage/logs/queue-worker.log"
echo ""
echo "워커를 중지하려면: ./stop-queue-worker.sh"
echo "상태 확인: ps aux | grep 'queue:work'"
