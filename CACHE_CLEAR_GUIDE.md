# 🔧 Laravel 캐시 클리어 가이드

배포 후 변경사항이 반영되지 않을 때 서버에서 실행하세요.

---

## 📍 **1단계: 프로젝트 경로 찾기**

```bash
# SSH로 서버 접속
ssh root@your-server-ip

# artisan 파일 위치 찾기
find /var/www -name "artisan" -type f 2>/dev/null

# 또는 /var/www 디렉토리 확인
ls -la /var/www/
```

**예상 결과:**
```
/var/www/storytelling/artisan
또는
/var/www/html/artisan
```

---

## 📍 **2단계: 프로젝트 디렉토리로 이동**

```bash
# 위에서 찾은 경로로 이동 (예시)
cd /var/www/storytelling

# 또는
cd /var/www/html

# artisan 파일이 있는지 확인
ls -la artisan
```

---

## 📍 **3단계: 모든 캐시 클리어**

```bash
# 라우트 캐시 클리어
php artisan route:clear

# 설정 캐시 클리어
php artisan config:clear

# 뷰 캐시 클리어
php artisan view:clear

# 애플리케이션 캐시 클리어
php artisan cache:clear

# 최적화 캐시 클리어
php artisan optimize:clear
```

---

## 📍 **4단계: 라우트 확인**

```bash
# batch-ai-evaluation 관련 라우트 확인
php artisan route:list | grep "batch-ai-evaluation"
```

**예상 출력:**
```
POST   admin/batch-ai-evaluation/start      admin.batch.ai.evaluation.start
GET    admin/batch-ai-evaluation/progress   admin.batch.ai.evaluation.progress
POST   admin/batch-ai-evaluation/retry      admin.batch.ai.evaluation.retry
```

---

## 📍 **5단계: 큐 워커 재시작**

```bash
# Supervisor로 관리하는 경우
sudo supervisorctl restart laravel-queue-worker:*

# 상태 확인
sudo supervisorctl status laravel-queue-worker:*
```

---

## 📍 **6단계: 웹 서버 재시작 (선택사항)**

```bash
# Nginx 재시작
sudo systemctl restart nginx

# PHP-FPM 재시작 (PHP 버전 확인 필요)
sudo systemctl restart php8.1-fpm
# 또는
sudo systemctl restart php8.2-fpm
# 또는
sudo systemctl restart php-fpm
```

---

## 🚀 **빠른 실행 (한 줄로)**

### **프로젝트가 /var/www/storytelling인 경우:**
```bash
cd /var/www/storytelling && php artisan route:clear && php artisan config:clear && php artisan view:clear && php artisan cache:clear && php artisan optimize:clear && php artisan route:list | grep "batch-ai-evaluation" && sudo supervisorctl restart laravel-queue-worker:*
```

### **프로젝트가 /var/www/html인 경우:**
```bash
cd /var/www/html && php artisan route:clear && php artisan config:clear && php artisan view:clear && php artisan cache:clear && php artisan optimize:clear && php artisan route:list | grep "batch-ai-evaluation" && sudo supervisorctl restart laravel-queue-worker:*
```

---

## 🔍 **문제가 계속되면**

### **1. 최신 코드 가져오기**
```bash
cd /var/www/storytelling  # 또는 /var/www/html
git pull origin master
```

### **2. Composer 의존성 업데이트**
```bash
composer install --no-dev --optimize-autoloader
```

### **3. 권한 확인**
```bash
# storage 디렉토리 권한 설정
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# bootstrap/cache 디렉토리 권한 설정
sudo chown -R www-data:www-data bootstrap/cache
sudo chmod -R 775 bootstrap/cache
```

### **4. 로그 확인**
```bash
# Laravel 로그
tail -50 storage/logs/laravel.log

# 실시간 로그 모니터링
tail -f storage/logs/laravel.log

# Nginx 에러 로그
tail -50 /var/log/nginx/error.log
```

---

## ✅ **성공 확인**

모든 명령어 실행 후:

1. **브라우저에서 강력 새로고침**: `Ctrl+F5` (Windows) 또는 `Cmd+Shift+R` (Mac)
2. **"영상 일괄 채점" 페이지** 접속
3. **브라우저 콘솔(F12)** 에서 오류 없는지 확인
4. **"새로고침" 버튼** 클릭해서 진행상황 확인

---

## 💡 **팁**

- 배포할 때마다 캐시 클리어를 습관화하세요
- 라우트나 설정을 변경했을 때는 반드시 해당 캐시를 클리어해야 합니다
- 프로덕션 환경에서는 `php artisan config:cache`로 성능을 최적화할 수 있지만, 개발/디버깅 중에는 캐시를 클리어한 상태로 유지하세요

