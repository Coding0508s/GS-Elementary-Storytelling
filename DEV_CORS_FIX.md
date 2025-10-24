# 🔧 개발환경 CORS 오류 해결 가이드

## 📋 발생한 문제

### **CORS Policy Block 오류**
```
Access to XMLHttpRequest at 'https://grapeseed-online.s3.ap-northeast-2.amazonaws.com/...' 
from origin 'http://127.0.0.1:8001' has been blocked by CORS policy: 
Response to preflight request doesn't pass access control check: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

### **네트워크 오류**
```
업로드 실패: Error: 네트워크 오류
Failed to load resource: net::ERR_FAILED
```

## ✅ 해결 방법

### **1단계: 개발환경용 S3 CORS 설정**

#### **A. AWS CLI 설정 확인**
```bash
# AWS CLI 설치 확인
aws --version

# AWS 자격 증명 설정
aws configure
```

#### **B. CORS 설정 실행**
```bash
# 개발환경용 CORS 설정 스크립트 실행
./fix-dev-cors.sh
```

### **2단계: 브라우저 캐시 클리어**

#### **Chrome/Edge:**
1. **개발자 도구** 열기 (F12)
2. **Network** 탭 클릭
3. **Disable cache** 체크박스 선택
4. **Ctrl+Shift+R** (하드 리프레시)

#### **Firefox:**
1. **개발자 도구** 열기 (F12)
2. **네트워크** 탭 클릭
3. **설정** → **캐시 비활성화** 선택
4. **Ctrl+Shift+R** (하드 리프레시)

### **3단계: 로컬 서버 재시작**
```bash
# Laravel 개발 서버 재시작
php artisan serve --host=127.0.0.1 --port=8001

# 또는 다른 포트로 실행
php artisan serve --host=127.0.0.1 --port=8000
```

## 📋 설정된 CORS 정책

### **허용된 오리진:**
```json
{
    "AllowedOrigins": [
        "http://127.0.0.1:8000",
        "http://127.0.0.1:8001", 
        "http://127.0.0.1:3000",
        "http://127.0.0.1:8080",
        "http://localhost:8000",
        "http://localhost:8001",
        "http://localhost:3000",
        "http://localhost:8080",
        "https://event.grapeseed.ac",
        "https://www.event.grapeseed.ac",
        "https://storytelling.grapeseed.ac"
    ]
}
```

### **허용된 메서드:**
- GET, PUT, POST, DELETE, HEAD, OPTIONS

### **허용된 헤더:**
- 모든 헤더 (*)

### **노출된 헤더:**
- ETag, x-amz-request-id, x-amz-version-id
- Access-Control-Allow-Origin, Access-Control-Allow-Methods, Access-Control-Allow-Headers

## 🚀 테스트 방법

### **1단계: CORS 설정 확인**
```bash
# AWS CLI로 CORS 설정 확인
aws s3api get-bucket-cors --bucket YOUR_BUCKET_NAME
```

### **2단계: 로컬 서버 실행**
```bash
# Laravel 개발 서버 실행
php artisan serve --host=127.0.0.1 --port=8001
```

### **3단계: 브라우저에서 테스트**
1. **http://127.0.0.1:8001** 접속
2. **개발자 도구** → **Console** 탭 열기
3. **파일 업로드** 시도
4. **CORS 오류** 확인

## ⚠️ 주의사항

### **개발환경 전용 설정**
- 이 CORS 설정은 **개발환경 전용**입니다
- **프로덕션 환경**에서는 보안을 위해 제한된 오리진만 허용해야 합니다

### **캐시 문제**
- CORS 설정 변경 후 **브라우저 캐시 클리어** 필수
- **개발자 도구**에서 **Disable cache** 옵션 사용 권장

### **포트 변경 시**
- 다른 포트를 사용하는 경우 `fix-dev-cors.sh` 스크립트 수정 필요
- `AllowedOrigins`에 새로운 포트 추가

## 🔍 문제 해결 체크리스트

- [ ] AWS CLI 설치 및 설정 완료
- [ ] S3 버킷 CORS 설정 완료
- [ ] 브라우저 캐시 클리어 완료
- [ ] 로컬 서버 재시작 완료
- [ ] 개발자 도구에서 CORS 오류 확인
- [ ] 파일 업로드 테스트 완료

## 📞 추가 도움

### **CORS 설정이 적용되지 않는 경우:**
1. **5분 대기** 후 다시 시도
2. **브라우저 완전 재시작**
3. **다른 브라우저**로 테스트
4. **AWS 콘솔**에서 직접 CORS 설정 확인

이제 개발환경에서 CORS 오류 없이 파일 업로드가 가능할 것입니다! 🎉
