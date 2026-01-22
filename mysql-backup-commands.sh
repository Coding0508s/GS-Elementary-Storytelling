#!/bin/bash

# MySQL 데이터베이스 백업 명령어 (서버에서 직접 실행)

cd /var/www/html/storytelling

# 백업 디렉토리 생성
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "백업 디렉토리: $BACKUP_DIR"

# Laravel 설정에서 데이터베이스 정보 가져오기
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -1 | tr -d '[:space:]')
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1 | tr -d '[:space:]')
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | tail -1 | tr -d '[:space:]')
DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null | tail -1 | tr -d '[:space:]')
DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" 2>/dev/null | tail -d '[:space:]')

# 기본값 설정
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

echo "데이터베이스: $DB_NAME"
echo "호스트: $DB_HOST:$DB_PORT"
echo "사용자: $DB_USER"

# mysqldump 확인
if ! command -v mysqldump &> /dev/null; then
    echo "❌ 오류: mysqldump 명령어를 찾을 수 없습니다."
    echo "MySQL 클라이언트를 설치하세요: apt install mysql-client"
    exit 1
fi

# 백업 파일 경로
BACKUP_SQL="$BACKUP_DIR/database.sql"

echo ""
echo "데이터베이스 백업 중..."

# mysqldump 실행
if [ -n "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_SQL" 2>&1
else
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "$BACKUP_SQL" 2>&1
fi

# 백업 결과 확인
if [ $? -eq 0 ] && [ -s "$BACKUP_SQL" ]; then
    echo "✅ MySQL 데이터베이스 백업 완료: $BACKUP_SQL"
    
    # .env 파일 백업
    if [ -f ".env" ]; then
        cp .env "$BACKUP_DIR/.env.backup"
        echo "✅ .env 파일 백업 완료"
    fi
    
    # 백업 정보 저장
    cat > "$BACKUP_DIR/backup_info.txt" << EOF
백업 일시: $(date '+%Y-%m-%d %H:%M:%S')
데이터베이스 타입: MySQL
데이터베이스명: $DB_NAME
호스트: $DB_HOST:$DB_PORT
백업 디렉토리: $BACKUP_DIR
프로젝트 경로: $(pwd)
EOF
    
    # 백업 파일 크기 확인
    BACKUP_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
    echo ""
    echo "백업 크기: $BACKUP_SIZE"
    echo ""
    echo "✅ 백업이 완료되었습니다!"
    echo "백업 위치: $BACKUP_DIR"
    echo ""
    ls -lh "$BACKUP_DIR"
else
    echo "❌ 오류: 데이터베이스 백업에 실패했습니다."
    echo ""
    echo "백업 파일 내용 확인:"
    head -20 "$BACKUP_SQL" 2>/dev/null || echo "백업 파일이 생성되지 않았습니다."
    echo ""
    echo "다음 사항을 확인하세요:"
    echo "1. MySQL 서버가 실행 중인지 확인"
    echo "2. 데이터베이스 사용자 권한 확인"
    echo "3. 데이터베이스 연결 정보 확인 (.env 파일)"
    exit 1
fi

