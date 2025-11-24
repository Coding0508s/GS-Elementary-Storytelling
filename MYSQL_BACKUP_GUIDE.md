# MySQL 데이터베이스 백업 가이드

## 🚀 빠른 백업 (서버에서 실행)

### 방법 1: 스크립트 사용 (권장)

```bash
cd /var/www/html/storytelling
./mysql-backup-commands.sh
```

### 방법 2: 직접 명령어 실행

```bash
cd /var/www/html/storytelling

# 백업 디렉토리 생성
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# 데이터베이스 정보 가져오기
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" | tail -1 | tr -d '[:space:]')
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" | tail -1 | tr -d '[:space:]')
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" | tail -1 | tr -d '[:space:]')
DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" | tail -1 | tr -d '[:space:]')
DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" | tail -1 | tr -d '[:space:]')

# 기본값 설정
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

# 백업 실행
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database.sql"

# .env 백업
cp .env "$BACKUP_DIR/.env.backup" 2>/dev/null || true

echo "백업 완료: $BACKUP_DIR"
ls -lh "$BACKUP_DIR"
```

### 방법 3: .env 파일에서 직접 읽기

```bash
cd /var/www/html/storytelling

# .env 파일에서 직접 읽기
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)

# 기본값 설정
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

# 백업 디렉토리 생성
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# 백업 실행
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database.sql"

echo "백업 완료: $BACKUP_DIR"
```

## 📦 백업 파일 위치

백업 파일은 다음 위치에 저장됩니다:
```
/var/www/html/storytelling/backups/YYYYMMDD_HHMMSS/
├── database.sql          # MySQL 덤프 파일
├── .env.backup          # 환경 설정 파일
└── backup_info.txt      # 백업 정보
```

## 🔄 백업 복원

### 로컬에서 복원

```bash
# 데이터베이스 생성 (필요한 경우)
mysql -u root -p -e "CREATE DATABASE database_name;"

# 백업 파일로 복원
mysql -u root -p database_name < backups/YYYYMMDD_HHMMSS/database.sql
```

### 서버에서 복원

```bash
cd /var/www/html/storytelling

# 데이터베이스 정보 확인
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" | tail -1 | tr -d '[:space:]')
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" | tail -1 | tr -d '[:space:]')
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" | tail -1 | tr -d '[:space:]')
DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" | tail -1 | tr -d '[:space:]')
DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" | tail -1 | tr -d '[:space:]')

# 복원 실행 (주의: 기존 데이터가 삭제됩니다!)
mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < backups/YYYYMMDD_HHMMSS/database.sql
```

## ⚠️ 주의사항

1. **백업 전 확인**
   - 데이터베이스 연결 정보가 올바른지 확인
   - 충분한 디스크 공간이 있는지 확인

2. **백업 파일 보안**
   - 백업 파일에는 민감한 데이터가 포함됩니다
   - 안전한 위치에 저장하고 접근 권한을 제한하세요

3. **복원 전 백업**
   - 복원하기 전에 현재 데이터베이스를 백업하세요
   - 복원은 기존 데이터를 덮어씁니다

## 🔧 문제 해결

### mysqldump 명령어를 찾을 수 없는 경우

```bash
# MySQL 클라이언트 설치
apt update
apt install mysql-client -y
```

### 권한 오류가 발생하는 경우

```bash
# MySQL 사용자에게 백업 권한 부여
mysql -u root -p
GRANT SELECT, LOCK TABLES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### 연결 오류가 발생하는 경우

```bash
# MySQL 서버 상태 확인
systemctl status mysql

# MySQL 서버 시작
systemctl start mysql

# 연결 테스트
mysql -h 127.0.0.1 -u username -p database_name
```

## 📊 백업 크기 확인

```bash
# 백업 파일 크기 확인
du -sh backups/YYYYMMDD_HHMMSS/database.sql

# 압축 (선택사항)
gzip backups/YYYYMMDD_HHMMSS/database.sql
```

## 🔄 자동 백업 설정

### Cron을 사용한 자동 백업

```bash
# crontab 편집
crontab -e

# 매일 새벽 2시에 백업 실행
0 2 * * * cd /var/www/html/storytelling && ./mysql-backup-commands.sh

# 또는 주 1회 (매주 일요일 새벽 2시)
0 2 * * 0 cd /var/www/html/storytelling && ./mysql-backup-commands.sh
```

### 오래된 백업 자동 삭제

```bash
# 30일 이상 된 백업 삭제
find /var/www/html/storytelling/backups -type d -mtime +30 -exec rm -rf {} \;
```

