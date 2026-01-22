# 🚀 서버 배포 가이드 - 시상 기능 추가

## 📋 배포 전 확인사항

이번 배포에는 다음 변경사항이 포함됩니다:
- ✅ `evaluations` 테이블에 `award` 컬럼 추가 (마이그레이션)
- ✅ 시상 선택 기능 추가
- ✅ 시상별 통계 카드 추가
- ✅ 실시간 통계 업데이트 기능

## 🔧 서버 배포 단계

### 방법 1: SSH로 직접 배포 (권장)

#### 1단계: 서버 접속
```bash
ssh root@your-server-ip
# 또는
ssh your-username@your-server-ip
```

#### 2단계: 프로젝트 디렉토리로 이동
```bash
cd /var/www/storytelling
# 또는 프로젝트가 있는 경로로 이동
```

#### 3단계: Git에서 최신 코드 가져오기
```bash
# 현재 상태 확인
git status

# 원격 저장소에서 최신 변경사항 가져오기
git fetch origin

# 변경사항 확인
git log HEAD..origin/master --oneline

# 최신 코드로 업데이트
git pull origin master
```

#### 4단계: Composer 의존성 업데이트
```bash
composer install --no-dev --optimize-autoloader
```

#### 5단계: 데이터베이스 마이그레이션 실행 ⚠️ 중요
```bash
# 마이그레이션 실행 (새로운 award 컬럼 추가)
php artisan migrate --force
```

#### 6단계: Laravel 캐시 클리어 및 재생성
```bash
# 캐시 클리어
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 프로덕션 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 7단계: 파일 권한 확인
```bash
chown -R www-data:www-data /var/www/storytelling
chmod -R 755 /var/www/storytelling
chmod -R 775 /var/www/storytelling/storage
chmod -R 775 /var/www/storytelling/bootstrap/cache
```

#### 8단계: 웹서버 재시작
```bash
# Nginx 사용 시
systemctl restart nginx
systemctl restart php8.2-fpm
# 또는
systemctl restart php8.1-fpm

# Apache 사용 시
systemctl restart apache2
```

#### 9단계: 배포 확인
```bash
# 마이그레이션 상태 확인
php artisan migrate:status

# 라우트 확인
php artisan route:list | grep award
```

---

### 방법 2: 배포 스크립트 사용

#### Contabo 서버 배포 스크립트 사용
```bash
# 로컬에서 실행
./deploy-contabo.sh your-server-ip /var/www/storytelling
```

#### 일반 배포 스크립트 사용
```bash
# 서버에서 실행
cd /var/www/storytelling
chmod +x deploy.sh
./deploy.sh
```

---

## ⚠️ 중요: 데이터베이스 마이그레이션

이번 배포에서 **새로운 마이그레이션 파일**이 추가되었습니다:
- `database/migrations/2025_11_20_090452_add_award_to_evaluations_table.php`

이 마이그레이션은 `evaluations` 테이블에 `award` 컬럼을 추가합니다.

### 마이그레이션 실행 전 확인사항
1. ✅ 데이터베이스 백업 (권장)
2. ✅ 서비스 중단 시간 계획 (마이그레이션은 빠르게 완료됨)
3. ✅ 마이그레이션 상태 확인

### 마이그레이션 실행
```bash
# 마이그레이션 상태 확인
php artisan migrate:status

# 마이그레이션 실행
php artisan migrate --force

# 마이그레이션 확인
php artisan migrate:status
```

### 마이그레이션 롤백 (문제 발생 시)
```bash
# 마지막 마이그레이션 롤백
php artisan migrate:rollback --step=1
```

---

## 🔍 배포 후 확인사항

### 1. 웹사이트 접속 확인
- [ ] 메인 페이지 정상 로드
- [ ] 관리자 로그인 페이지 정상 로드
- [ ] 평가 순위 페이지 접속 확인

### 2. 시상 기능 테스트
- [ ] 평가 순위 페이지에서 시상 드롭다운 표시 확인
- [ ] 시상 선택 시 저장되는지 확인
- [ ] 시상별 통계 카드 표시 확인
- [ ] 시상 변경 시 통계 실시간 업데이트 확인

### 3. 데이터베이스 확인
```bash
# MySQL 접속
mysql -u storytelling_user -p storytelling_contest

# award 컬럼 확인
DESCRIBE evaluations;
# 또는
SHOW COLUMNS FROM evaluations LIKE 'award';

# 시상 데이터 확인
SELECT award, COUNT(*) as count FROM evaluations GROUP BY award;
```

### 4. 로그 확인
```bash
# Laravel 로그 확인
tail -f /var/www/storytelling/storage/logs/laravel.log

# 에러가 있는지 확인
grep -i error /var/www/storytelling/storage/logs/laravel.log | tail -20
```

---

## 🚨 문제 해결

### 문제 1: 마이그레이션 실패
```bash
# 에러 메시지 확인
php artisan migrate --force

# 마이그레이션 상태 확인
php artisan migrate:status

# 특정 마이그레이션만 실행
php artisan migrate --path=database/migrations/2025_11_20_090452_add_award_to_evaluations_table.php
```

### 문제 2: 시상 기능이 작동하지 않음
```bash
# 캐시 완전히 클리어
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 라우트 확인
php artisan route:list | grep award

# 권한 확인
ls -la app/Http/Controllers/AdminController.php
ls -la app/Models/Evaluation.php
```

### 문제 3: 500 에러 발생
```bash
# 로그 확인
tail -50 /var/www/storytelling/storage/logs/laravel.log

# 권한 재설정
chown -R www-data:www-data /var/www/storytelling
chmod -R 775 /var/www/storytelling/storage
chmod -R 775 /var/www/storytelling/bootstrap/cache

# 웹서버 재시작
systemctl restart nginx
systemctl restart php8.2-fpm
```

---

## 📝 빠른 배포 명령어 (한 줄씩 실행)

```bash
# 1. 서버 접속 후
cd /var/www/storytelling

# 2. Git 업데이트
git pull origin master

# 3. 의존성 업데이트
composer install --no-dev --optimize-autoloader

# 4. 마이그레이션 실행
php artisan migrate --force

# 5. 캐시 클리어 및 재생성
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 6. 권한 설정
chown -R www-data:www-data . && chmod -R 775 storage bootstrap/cache

# 7. 웹서버 재시작
systemctl restart nginx && systemctl restart php8.2-fpm
```

---

## ✅ 배포 완료 체크리스트

- [ ] Git에서 최신 코드 가져오기 완료
- [ ] Composer 의존성 업데이트 완료
- [ ] 데이터베이스 마이그레이션 실행 완료
- [ ] Laravel 캐시 클리어 및 재생성 완료
- [ ] 파일 권한 설정 완료
- [ ] 웹서버 재시작 완료
- [ ] 웹사이트 접속 테스트 완료
- [ ] 시상 기능 테스트 완료
- [ ] 시상 통계 카드 표시 확인 완료
- [ ] 로그에 에러 없음 확인 완료

---

## 📞 문제 발생 시

배포 중 문제가 발생하면:
1. **로그 확인**: `/var/www/storytelling/storage/logs/laravel.log`
2. **마이그레이션 상태 확인**: `php artisan migrate:status`
3. **라우트 확인**: `php artisan route:list`
4. **데이터베이스 연결 확인**: `php artisan migrate:status`

---

**배포 완료 후 반드시 시상 기능이 정상 작동하는지 테스트하세요!** 🎉

