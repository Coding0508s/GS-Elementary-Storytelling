# 🔧 CORS 오류 해결 가이드

## 📋 발생한 오류들

### **1. CORS Policy Block 오류**
```
Access to XMLHttpRequest at 'https://grapeseed-online.s3-accelerate.dualstack.amazonaws.com/...' 
from origin 'https://event.grapeseed.ac' has been blocked by CORS policy: 
Response to preflight request doesn't pass access control check: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

### **2. Unsafe Header 오류**
```
Refused to set unsafe header 'Connection'
```

### **3. 네트워크 오류**
```
업로드 실패: Error: 네트워크 오류
Failed to load resource: net::ERR_FAILED
```

## ✅ 해결 방법

### **1. Unsafe Header 오류 수정 (완료)**
- **문제**: `Connection: keep-alive` 헤더가 브라우저에서 차단됨
- **해결**: 안전한 헤더만 사용하도록 수정
- **변경사항**: `Connection` 헤더 제거, `Cache-Control`만 유지

### **2. S3 Transfer Acceleration 비활성화 (완료)**
- **문제**: Transfer Acceleration 엔드포인트에서 CORS 설정 문제
- **해결**: 일반 S3 엔드포인트 사용
- **변경사항**: 
  - `use_accelerate_endpoint: false`
  - `use_dual_stack_endpoint: false`

### **3. S3 버킷 CORS 설정 (필수)**
```bash
# CORS 설정 스크립트 실행
./fix-s3-cors.sh
```

### **4. 브라우저 캐시 클리어**
- **개발자 도구** → **Network** 탭 → **Disable cache** 체크
- **Ctrl+Shift+R** (하드 리프레시)
- **브라우저 캐시 완전 삭제**

## 🚀 실행 방법

### **1단계: 코드 변경사항 적용**
```bash
# 로컬에서
git pull origin master
php artisan config:clear
php artisan cache:clear
```

### **2단계: S3 CORS 설정**
```bash
# AWS CLI 설정 확인
aws configure

# CORS 설정 실행
./fix-s3-cors.sh
```

### **3단계: 서버 업데이트**
```bash
# 서버에서
cd /var/www/html/storytelling
git pull origin master
php artisan config:clear
php artisan cache:clear
systemctl restart nginx
systemctl restart php8.1-fpm
```

## 📋 수정된 CORS 설정

```json
{
    "CORSRules": [
        {
            "AllowedHeaders": ["*"],
            "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
            "AllowedOrigins": [
                "https://event.grapeseed.ac",
                "https://www.event.grapeseed.ac",
                "https://storytelling.grapeseed.ac"
            ],
            "ExposeHeaders": [
                "ETag",
                "x-amz-request-id",
                "x-amz-version-id"
            ],
            "MaxAgeSeconds": 3600
        }
    ]
}
```

## ⚠️ 중요사항

1. **S3 버킷 CORS 설정이 필수**입니다
2. **Transfer Acceleration 비활성화**로 안정성 확보
3. **브라우저 캐시 클리어** 후 테스트
4. **CORS 설정 변경**은 최대 5분 소요

## 🔍 문제 해결 체크리스트

- [ ] Unsafe header 오류 수정 완료
- [ ] S3 Transfer Acceleration 비활성화 완료
- [ ] S3 버킷 CORS 설정 완료
- [ ] 브라우저 캐시 클리어 완료
- [ ] 서버 재시작 완료
- [ ] 업로드 테스트 완료

이제 CORS 오류가 해결되어 정상적인 업로드가 가능할 것입니다! 🎉
