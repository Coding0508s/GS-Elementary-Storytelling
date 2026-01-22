# Git Pull 충돌 해결 방법

## 문제 상황

서버에 이미 `backup-database.sh` 파일이 있는데, Git 저장소에도 같은 파일이 있어서 충돌이 발생했습니다.

## 해결 방법

### 방법 1: 서버의 파일을 백업하고 Git 버전 사용 (권장)

서버에서 다음 명령어를 실행하세요:

```bash
cd /var/www/html/storytelling

# 기존 파일 백업
mv backup-database.sh backup-database.sh.backup

# Git pull 다시 시도
git pull origin master

# 백업 파일과 새 파일 비교 (필요한 경우)
diff backup-database.sh.backup backup-database.sh
```

### 방법 2: 서버의 파일을 삭제하고 Git 버전 사용

```bash
cd /var/www/html/storytelling

# 기존 파일 삭제
rm backup-database.sh

# Git pull 다시 시도
git pull origin master
```

### 방법 3: 서버의 파일을 유지하고 Git 버전 무시

```bash
cd /var/www/html/storytelling

# .gitignore에 추가 (이미 있다면 스킵)
echo "backup-database.sh" >> .gitignore

# Git pull 강제 실행 (주의: 서버 파일이 우선)
git pull origin master --allow-unrelated-histories
```

## 백업 파일 복사 명령어 수정

백업 파일을 복사할 때는 실제 디렉토리 이름을 사용해야 합니다:

```bash
cd /var/www/html/storytelling

# 실제 백업 디렉토리 확인
ls -la backups/

# 최신 백업 디렉토리로 복사 (예: 20251124_062413)
cp backups/20251124_062413/database_*.sql.gz /backup/mysql/

# 또는 모든 백업 파일 복사
cp backups/*/database_*.sql.gz /backup/mysql/ 2>/dev/null || true
```

## 전체 배포 명령어 (수정된 버전)

```bash
cd /var/www/html/storytelling

# 1. Git 충돌 해결
mv backup-database.sh backup-database.sh.backup 2>/dev/null || true

# 2. Git pull
git pull origin master

# 3. Composer 의존성 업데이트
composer install --no-dev --optimize-autoloader

# 4. 마이그레이션 실행
php artisan migrate --force

# 5. 캐시 클리어 및 재생성
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. 파일 권한 설정
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 7. PHP-FPM 재시작
systemctl restart php8.2-fpm || systemctl restart php-fpm

echo "✅ 배포 완료!"
```

