# 서버 배포 가이드

## 📋 배포 전 체크리스트

### 1. 데이터베이스 백업 (필수!)

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

# 백업 실행 (압축 포함)
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/database_$(date +%Y%m%d_%H%M%S).sql.gz"

# .env 백업
cp .env "$BACKUP_DIR/.env.backup" 2>/dev/null || true

echo "✅ 백업 완료: $BACKUP_DIR"
ls -lh "$BACKUP_DIR"

# 시스템 백업 디렉토리로도 복사 (선택사항)
cp "$BACKUP_DIR/database_"*.sql.gz /backup/mysql/ 2>/dev/null || true
```

### 2. Git에서 최신 코드 가져오기

```bash
cd /var/www/html/storytelling

# 현재 브랜치 확인
git branch

# 최신 코드 가져오기
git pull origin master

# 또는 특정 커밋으로 이동
# git checkout 2320a8b
```

### 3. 의존성 업데이트

```bash
cd /var/www/html/storytelling

# Composer 의존성 업데이트
composer install --no-dev --optimize-autoloader

# 또는 개발 환경인 경우
# composer install
```

### 4. 마이그레이션 실행

```bash
cd /var/www/html/storytelling

# 마이그레이션 상태 확인
php artisan migrate:status

# 마이그레이션 실행
php artisan migrate

# 마이그레이션 롤백이 필요한 경우 (주의!)
# php artisan migrate:rollback --step=1
```

### 5. 캐시 클리어

```bash
cd /var/www/html/storytelling

# 모든 캐시 클리어
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 캐시 재생성 (프로덕션 환경)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. 파일 권한 설정

```bash
cd /var/www/html/storytelling

# 저장소 및 캐시 디렉토리 권한 설정
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 또는 root로 실행 중인 경우
# chown -R www-data:www-data storage bootstrap/cache
# chmod -R 775 storage bootstrap/cache
```

### 7. 환경 설정 확인

```bash
cd /var/www/html/storytelling

# .env 파일 확인
cat .env | grep -E "APP_ENV|APP_DEBUG|DB_"

# 프로덕션 환경인 경우
# APP_ENV=production
# APP_DEBUG=false
```

### 8. 서비스 재시작 (필요한 경우)

```bash
# PHP-FPM 재시작
sudo systemctl restart php8.2-fpm
# 또는
sudo systemctl restart php-fpm

# Nginx 재시작 (필요한 경우)
sudo systemctl restart nginx

# 서비스 상태 확인
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
```

## 🚀 전체 배포 스크립트 (한 번에 실행)

```bash
#!/bin/bash
set -e

cd /var/www/html/storytelling

echo "=== 배포 시작 ==="

# 1. 데이터베이스 백업
echo "1. 데이터베이스 백업 중..."
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" | tail -1 | tr -d '[:space:]')
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" | tail -1 | tr -d '[:space:]')
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" | tail -1 | tr -d '[:space:]')
DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" | tail -1 | tr -d '[:space:]')
DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" | tail -1 | tr -d '[:space:]')

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/database_$(date +%Y%m%d_%H%M%S).sql.gz"
cp .env "$BACKUP_DIR/.env.backup" 2>/dev/null || true
echo "✅ 백업 완료: $BACKUP_DIR"

# 2. Git 업데이트
echo "2. Git에서 최신 코드 가져오는 중..."
git pull origin master

# 3. Composer 의존성 업데이트
echo "3. Composer 의존성 업데이트 중..."
composer install --no-dev --optimize-autoloader

# 4. 마이그레이션 실행
echo "4. 마이그레이션 실행 중..."
php artisan migrate --force

# 5. 캐시 클리어 및 재생성
echo "5. 캐시 클리어 및 재생성 중..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. 파일 권한 설정
echo "6. 파일 권한 설정 중..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 7. PHP-FPM 재시작
echo "7. PHP-FPM 재시작 중..."
systemctl restart php8.2-fpm || systemctl restart php-fpm

echo ""
echo "=== 배포 완료 ==="
echo "백업 위치: $BACKUP_DIR"
```

## ⚠️ 주의사항

1. **배포 전 반드시 백업**
   - 데이터베이스 백업은 필수입니다
   - 백업이 성공적으로 완료되었는지 확인하세요

2. **마이그레이션 주의**
   - 마이그레이션은 데이터베이스 구조를 변경할 수 있습니다
   - 마이그레이션 전에 백업을 확인하세요

3. **환경 설정**
   - `.env` 파일의 `APP_ENV`와 `APP_DEBUG` 설정을 확인하세요
   - 프로덕션 환경에서는 `APP_DEBUG=false`로 설정하세요

4. **권한 설정**
   - `storage`와 `bootstrap/cache` 디렉토리는 웹 서버가 쓰기 가능해야 합니다

5. **서비스 재시작**
   - PHP-FPM 재시작은 필요할 수 있습니다
   - 변경사항이 반영되지 않으면 재시작하세요

## 🔍 배포 후 확인

```bash
# 애플리케이션 로그 확인
tail -f storage/logs/laravel.log

# 라우트 확인
php artisan route:list

# 마이그레이션 상태 확인
php artisan migrate:status

# 캐시 상태 확인
php artisan config:show
```

## 🆘 문제 발생 시

1. **마이그레이션 오류**
   ```bash
   # 마이그레이션 롤백
   php artisan migrate:rollback --step=1
   
   # 백업에서 복원
   gunzip < backups/YYYYMMDD_HHMMSS/database_*.sql.gz | mysql -u user -p database_name
   ```

2. **캐시 문제**
   ```bash
   # 모든 캐시 강제 클리어
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   rm -rf bootstrap/cache/*.php
   ```

3. **권한 문제**
   ```bash
   # 권한 재설정
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

