#!/bin/bash

# Laravel Queue Worker 중지 스크립트

echo "Laravel Queue Worker를 중지합니다..."

# 프로젝트 디렉토리로 이동
cd "$(dirname "$0")"

# PID 파일이 있는지 확인
if [ -f "storage/logs/queue-worker.pid" ]; then
    PID=$(cat storage/logs/queue-worker.pid)
    echo "PID $PID 프로세스를 중지합니다..."
    
    # 프로세스가 실행 중인지 확인
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Queue Worker가 중지되었습니다."
    else
        echo "PID $PID 프로세스를 찾을 수 없습니다."
    fi
    
    # PID 파일 삭제
    rm -f storage/logs/queue-worker.pid
else
    echo "PID 파일을 찾을 수 없습니다. 수동으로 프로세스를 찾아 중지하세요."
    echo "실행 중인 큐 워커:"
    ps aux | grep 'queue:work' | grep -v grep
fi
