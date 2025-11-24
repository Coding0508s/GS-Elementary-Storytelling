# 서버에 백업 스크립트 업로드 방법

## 방법 1: SCP로 파일 업로드 (권장)

로컬에서 서버로 백업 스크립트를 업로드:

```bash
# 로컬에서 실행
scp backup-database.sh user@your-server.com:/var/www/html/storytelling/
```

## 방법 2: 서버에서 직접 생성

서버에 SSH로 접속한 후:

```bash
# 서버에서 실행
cd /var/www/html/storytelling
nano backup-database.sh
```

그 다음 아래 전체 스크립트 내용을 복사하여 붙여넣기:

