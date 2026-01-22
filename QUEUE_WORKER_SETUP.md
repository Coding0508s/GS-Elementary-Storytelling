# Laravel Queue Worker 설정 가이드 (서버용)

## 개요
AI 일괄 채점 기능이 정상적으로 작동하려면 서버에서 Laravel Queue Worker가 실행되어야 합니다.

## 문제 상황
- 일괄 AI 채점 시작 버튼을 클릭하면 계속 대기 상태
- 원인: 서버에서 큐 워커가 실행되지 않음

## 해결 방법

### 1. 서버에 접속
```bash
ssh root@event.grapeseed.ac
# 또는
ssh root@YOUR_SERVER_IP
```

### 2. 프로젝트 디렉토리로 이동
```bash
cd /var/www/html
```

### 3. 큐 워커 설정 스크립트 다운로드
프로젝트 루트에 `setup-queue-server.sh` 파일이 있는지 확인하고, 실행 권한을 부여합니다.

```bash
chmod +x setup-queue-server.sh
```

### 4. 큐 워커 설정 스크립트 실행
```bash
sudo ./setup-queue-server.sh
```

이 스크립트는 다음 작업을 자동으로 수행합니다:
- Supervisor 설치
- Laravel Queue Worker 설정 파일 생성
- Supervisor를 통한 큐 워커 자동 시작 및 재시작 설정

## 수동 설정 방법

스크립트를 사용하지 않고 수동으로 설정하려면:

### 1. Supervisor 설치
```bash
sudo apt update
sudo apt install -y supervisor
```

### 2. Supervisor 설정 파일 생성
```bash
sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf
```

다음 내용을 입력:
```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --verbose --tries=3 --timeout=600 --sleep=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

### 3. Supervisor 재시작
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

## 큐 워커 관리 명령어

### 상태 확인
```bash
sudo supervisorctl status laravel-queue-worker:*
```

### 시작
```bash
sudo supervisorctl start laravel-queue-worker:*
```

### 중지
```bash
sudo supervisorctl stop laravel-queue-worker:*
```

### 재시작
```bash
sudo supervisorctl restart laravel-queue-worker:*
```

### 로그 확인
```bash
tail -f /var/www/html/storage/logs/queue-worker.log
```

## 큐 워커 설정 설명

- `--tries=3`: 실패 시 최대 3번 재시도
- `--timeout=600`: 각 작업의 최대 실행 시간 (10분)
- `--sleep=3`: 작업이 없을 때 대기 시간 (3초)
- `--max-time=3600`: 워커가 1시간마다 재시작 (메모리 누수 방지)
- `numprocs=2`: 동시에 2개의 큐 워커 실행

## 코드 배포 후

코드를 업데이트한 후에는 큐 워커를 재시작해야 합니다:

```bash
sudo supervisorctl restart laravel-queue-worker:*
```

또는 Laravel Horizon을 사용하는 경우:
```bash
php artisan horizon:terminate
```

## 문제 해결

### 큐 워커가 시작되지 않는 경우
1. 로그 확인:
```bash
tail -f /var/www/html/storage/logs/queue-worker.log
tail -f /var/www/html/storage/logs/laravel.log
```

2. 권한 확인:
```bash
sudo chown -R www-data:www-data /var/www/html/storage
sudo chmod -R 775 /var/www/html/storage
```

3. Supervisor 로그 확인:
```bash
sudo tail -f /var/log/supervisor/supervisord.log
```

### 큐 작업이 처리되지 않는 경우
1. 데이터베이스 연결 확인:
```bash
php artisan tinker
# Tinker에서:
DB::connection()->getPdo();
```

2. 큐 테이블 확인:
```bash
php artisan queue:monitor
```

3. 실패한 작업 확인:
```bash
php artisan queue:failed
```

4. 실패한 작업 재시도:
```bash
php artisan queue:retry all
```

## 모니터링

큐 워커가 정상적으로 작동하는지 확인하려면:

```bash
# 프로세스 확인
ps aux | grep "queue:work"

# Supervisor 상태 확인
sudo supervisorctl status

# 큐 테이블 확인
php artisan tinker
# Tinker에서:
\DB::table('jobs')->count();
```

## 참고사항

- 큐 워커는 서버 재부팅 시 자동으로 시작됩니다 (`autostart=true`)
- 큐 워커가 비정상 종료되면 자동으로 재시작됩니다 (`autorestart=true`)
- 1시간마다 자동으로 재시작하여 메모리 누수를 방지합니다 (`--max-time=3600`)
- 2개의 워커가 동시에 실행되어 병렬 처리가 가능합니다 (`numprocs=2`)

## 배포 체크리스트

서버 배포 시 다음 사항을 확인하세요:

- [ ] Supervisor 설치 완료
- [ ] 큐 워커 설정 파일 생성
- [ ] 큐 워커 시작 확인
- [ ] 로그 파일 권한 확인
- [ ] 테스트 작업 실행 (일괄 AI 채점)
- [ ] 진행상황 모니터링 확인

## 추가 정보

- Laravel Queue 문서: https://laravel.com/docs/queues
- Supervisor 문서: http://supervisord.org/

