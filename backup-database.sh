#!/bin/bash

# 배포 전 데이터베이스 백업 스크립트
# SQLite 데이터베이스를 백업합니다.

set -e  # 오류 발생 시 스크립트 중단

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 로그 함수
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 프로젝트 디렉토리 확인
if [ ! -f "artisan" ]; then
    log_error "Laravel 프로젝트 디렉토리가 아닙니다. artisan 파일을 찾을 수 없습니다."
    exit 1
fi

# 데이터베이스 설정 확인
DB_CONNECTION=$(php artisan tinker --execute="echo config('database.default');" 2>/dev/null | tail -1 | tr -d '[:space:]')

if [ -z "$DB_CONNECTION" ]; then
    log_error "데이터베이스 연결 설정을 확인할 수 없습니다."
    exit 1
fi

log_info "데이터베이스 타입: $DB_CONNECTION"

# 백업 디렉토리 생성
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

log_info "백업 디렉토리: $BACKUP_DIR"

# SQLite 데이터베이스 백업
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_PATH=$(php artisan tinker --execute="echo config('database.connections.sqlite.database');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    
    if [ -z "$DB_PATH" ]; then
        log_error "SQLite 데이터베이스 경로를 찾을 수 없습니다."
        exit 1
    fi
    
    # 상대 경로를 절대 경로로 변환
    if [[ ! "$DB_PATH" = /* ]]; then
        DB_PATH="$(pwd)/$DB_PATH"
    fi
    
    if [ ! -f "$DB_PATH" ]; then
        log_error "데이터베이스 파일을 찾을 수 없습니다: $DB_PATH"
        exit 1
    fi
    
    log_info "데이터베이스 파일: $DB_PATH"
    
    # 백업 파일명
    BACKUP_FILE="$BACKUP_DIR/database.sqlite"
    BACKUP_SQL="$BACKUP_DIR/database.sql"
    
    # 1. SQLite 파일 직접 복사
    log_info "SQLite 데이터베이스 파일을 복사하는 중..."
    cp "$DB_PATH" "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        log_info "✅ 데이터베이스 파일 백업 완료: $BACKUP_FILE"
    else
        log_error "데이터베이스 파일 백업 실패"
        exit 1
    fi
    
    # 2. SQL 덤프 생성 (선택사항)
    if command -v sqlite3 &> /dev/null; then
        log_info "SQL 덤프를 생성하는 중..."
        sqlite3 "$DB_PATH" .dump > "$BACKUP_SQL" 2>/dev/null
        
        if [ $? -eq 0 ]; then
            log_info "✅ SQL 덤프 백업 완료: $BACKUP_SQL"
        else
            log_warning "SQL 덤프 생성 실패 (sqlite3 명령어 확인 필요)"
        fi
    else
        log_warning "sqlite3 명령어를 찾을 수 없습니다. SQL 덤프를 생성하지 않습니다."
    fi

# MySQL/MariaDB 데이터베이스 백업
elif [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    if ! command -v mysqldump &> /dev/null; then
        log_error "mysqldump 명령어를 찾을 수 없습니다."
        exit 1
    fi
    
    DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    
    BACKUP_SQL="$BACKUP_DIR/database.sql"
    
    log_info "MySQL 데이터베이스를 백업하는 중..."
    log_info "데이터베이스: $DB_NAME"
    log_info "호스트: $DB_HOST:$DB_PORT"
    
    if [ -n "$DB_PASS" ]; then
        mysqldump -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_SQL"
    else
        mysqldump -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" "$DB_NAME" > "$BACKUP_SQL"
    fi
    
    if [ $? -eq 0 ]; then
        log_info "✅ MySQL 데이터베이스 백업 완료: $BACKUP_SQL"
    else
        log_error "MySQL 데이터베이스 백업 실패"
        exit 1
    fi

# PostgreSQL 데이터베이스 백업
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    if ! command -v pg_dump &> /dev/null; then
        log_error "pg_dump 명령어를 찾을 수 없습니다."
        exit 1
    fi
    
    DB_NAME=$(php artisan tinker --execute="echo config('database.connections.pgsql.database');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_USER=$(php artisan tinker --execute="echo config('database.connections.pgsql.username');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_PASS=$(php artisan tinker --execute="echo config('database.connections.pgsql.password');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.pgsql.host');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    DB_PORT=$(php artisan tinker --execute="echo config('database.connections.pgsql.port');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    
    BACKUP_SQL="$BACKUP_DIR/database.sql"
    
    log_info "PostgreSQL 데이터베이스를 백업하는 중..."
    
    export PGPASSWORD="$DB_PASS"
    pg_dump -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" -d "$DB_NAME" > "$BACKUP_SQL"
    unset PGPASSWORD
    
    if [ $? -eq 0 ]; then
        log_info "✅ PostgreSQL 데이터베이스 백업 완료: $BACKUP_SQL"
    else
        log_error "PostgreSQL 데이터베이스 백업 실패"
        exit 1
    fi

else
    log_error "지원하지 않는 데이터베이스 타입: $DB_CONNECTION"
    exit 1
fi

# .env 파일 백업 (선택사항)
if [ -f ".env" ]; then
    log_info ".env 파일을 백업하는 중..."
    cp .env "$BACKUP_DIR/.env.backup"
    log_info "✅ .env 파일 백업 완료"
fi

# 백업 정보 파일 생성
cat > "$BACKUP_DIR/backup_info.txt" << EOF
백업 일시: $(date '+%Y-%m-%d %H:%M:%S')
데이터베이스 타입: $DB_CONNECTION
백업 디렉토리: $BACKUP_DIR
프로젝트 경로: $(pwd)
EOF

log_info "✅ 백업 정보 파일 생성 완료"

# 백업 파일 크기 확인
BACKUP_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log_info "백업 크기: $BACKUP_SIZE"

echo ""
log_info "🎉 백업이 완료되었습니다!"
echo ""
log_info "백업 위치: $BACKUP_DIR"
echo ""
log_warning "⚠️  배포 전에 백업 파일이 안전한 위치에 저장되었는지 확인하세요."
echo ""

