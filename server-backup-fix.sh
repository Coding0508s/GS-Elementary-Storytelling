#!/bin/bash

# 서버에서 실행할 수정된 백업 명령어

cd /var/www/html/storytelling

# 백업 디렉토리 생성
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "백업 디렉토리: $BACKUP_DIR"

# 데이터베이스 경로 확인 (여러 방법 시도)
DB_PATH=$(php artisan tinker --execute="echo config('database.connections.sqlite.database');" 2>/dev/null | tail -1 | tr -d '[:space:]')

echo "설정에서 읽은 경로: $DB_PATH"

# 상대 경로인 경우 절대 경로로 변환
if [[ ! "$DB_PATH" = /* ]]; then
    # 상대 경로인 경우
    if [[ "$DB_PATH" == database/* ]]; then
        DB_PATH="$(pwd)/$DB_PATH"
    else
        # database/ 디렉토리에서 찾기
        DB_PATH="$(pwd)/database/$DB_PATH"
    fi
fi

echo "변환된 경로: $DB_PATH"

# 실제 파일 존재 여부 확인
if [ ! -f "$DB_PATH" ]; then
    echo "경고: $DB_PATH 파일을 찾을 수 없습니다."
    echo "가능한 데이터베이스 파일 검색 중..."
    
    # 일반적인 위치에서 찾기
    POSSIBLE_PATHS=(
        "$(pwd)/database/database.sqlite"
        "$(pwd)/database.sqlite"
        "$(pwd)/storage/database.sqlite"
        "$(pwd)/$DB_PATH"
    )
    
    for path in "${POSSIBLE_PATHS[@]}"; do
        if [ -f "$path" ]; then
            DB_PATH="$path"
            echo "데이터베이스 파일 발견: $DB_PATH"
            break
        fi
    done
fi

# 최종 확인
if [ -f "$DB_PATH" ]; then
    echo "데이터베이스 파일 백업 중: $DB_PATH"
    cp "$DB_PATH" "$BACKUP_DIR/database.sqlite"
    
    # SQL 덤프도 생성 (가능한 경우)
    if command -v sqlite3 &> /dev/null; then
        sqlite3 "$DB_PATH" .dump > "$BACKUP_DIR/database.sql" 2>/dev/null
        echo "SQL 덤프 생성 완료"
    fi
    
    # .env 파일 백업
    if [ -f ".env" ]; then
        cp .env "$BACKUP_DIR/.env.backup"
        echo ".env 파일 백업 완료"
    fi
    
    # 백업 정보 저장
    cat > "$BACKUP_DIR/backup_info.txt" << EOF
백업 일시: $(date '+%Y-%m-%d %H:%M:%S')
데이터베이스 경로: $DB_PATH
백업 디렉토리: $BACKUP_DIR
프로젝트 경로: $(pwd)
EOF
    
    echo ""
    echo "✅ 백업 완료!"
    echo "백업 위치: $BACKUP_DIR"
    echo ""
    ls -lh "$BACKUP_DIR"
else
    echo "❌ 오류: 데이터베이스 파일을 찾을 수 없습니다."
    echo ""
    echo "다음 위치에서 데이터베이스 파일을 찾아보세요:"
    find "$(pwd)" -name "*.sqlite" -o -name "*.db" 2>/dev/null | head -10
    exit 1
fi

