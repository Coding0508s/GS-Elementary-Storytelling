#!/bin/bash

# ========================================
# 개발환경 S3 CORS 설정 스크립트
# 로컬 개발환경 (127.0.0.1, localhost) 지원
# ========================================

echo "🔧 개발환경 S3 CORS 설정을 시작합니다..."

# AWS CLI 설치 확인
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI가 설치되지 않았습니다."
    echo "다음 명령어로 설치하세요:"
    echo "curl 'https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip' -o 'awscliv2.zip'"
    echo "unzip awscliv2.zip"
    echo "sudo ./aws/install"
    exit 1
fi

# AWS 자격 증명 확인
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS 자격 증명이 설정되지 않았습니다."
    echo "다음 명령어로 설정하세요:"
    echo "aws configure"
    exit 1
fi

# 환경 변수에서 버킷명 가져오기
BUCKET_NAME=$(grep AWS_BUCKET .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

if [ -z "$BUCKET_NAME" ]; then
    echo "❌ .env 파일에서 AWS_BUCKET을 찾을 수 없습니다."
    exit 1
fi

echo "📦 버킷명: $BUCKET_NAME"

# 개발환경용 CORS 설정 JSON 생성
CORS_CONFIG='{
    "CORSRules": [
        {
            "AllowedHeaders": [
                "*"
            ],
            "AllowedMethods": [
                "GET",
                "PUT",
                "POST",
                "DELETE",
                "HEAD"
            ],
            "AllowedOrigins": [
                "http://127.0.0.1:8000",
                "http://127.0.0.1:8001",
                "http://127.0.0.1:8002",
                "http://127.0.0.1:3000",
                "http://127.0.0.1:8080",
                "http://localhost:8000",
                "http://localhost:8001",
                "http://localhost:8002",
                "http://localhost:3000",
                "http://localhost:8080",
                "https://event.grapeseed.ac",
                "https://www.event.grapeseed.ac",
                "https://storytelling.grapeseed.ac"
            ],
            "ExposeHeaders": [
                "ETag",
                "x-amz-request-id",
                "x-amz-version-id",
                "x-amz-server-side-encryption",
                "x-amz-server-side-encryption-aws-kms-key-id",
                "Access-Control-Allow-Origin",
                "Access-Control-Allow-Methods",
                "Access-Control-Allow-Headers"
            ],
            "MaxAgeSeconds": 3600
        }
    ]
}'

# 임시 파일 생성
TEMP_CORS_FILE="/tmp/dev-cors-config.json"
echo "$CORS_CONFIG" > "$TEMP_CORS_FILE"

echo "🔧 S3 버킷에 개발환경 CORS 설정을 적용합니다..."

# S3 버킷 CORS 설정 적용
if aws s3api put-bucket-cors --bucket "$BUCKET_NAME" --cors-configuration file://"$TEMP_CORS_FILE"; then
    echo "✅ 개발환경 CORS 설정이 성공적으로 적용되었습니다!"
    
    # 설정 확인
    echo "🔍 적용된 CORS 설정을 확인합니다..."
    aws s3api get-bucket-cors --bucket "$BUCKET_NAME"
    
else
    echo "❌ CORS 설정 적용에 실패했습니다."
    echo "버킷명을 확인하고 AWS 권한을 점검하세요."
    exit 1
fi

# 임시 파일 삭제
rm -f "$TEMP_CORS_FILE"

echo "🎉 개발환경 S3 CORS 설정이 완료되었습니다!"
echo ""
echo "📋 설정된 허용 오리진:"
echo "   - http://127.0.0.1:8000 (로컬 개발)"
echo "   - http://127.0.0.1:8001 (로컬 개발)"
echo "   - http://127.0.0.1:3000 (로컬 개발)"
echo "   - http://127.0.0.1:8080 (로컬 개발)"
echo "   - http://localhost:8000 (로컬 개발)"
echo "   - http://localhost:8001 (로컬 개발)"
echo "   - http://localhost:3000 (로컬 개발)"
echo "   - http://localhost:8080 (로컬 개발)"
echo "   - https://event.grapeseed.ac (프로덕션)"
echo "   - https://www.event.grapeseed.ac (프로덕션)"
echo "   - https://storytelling.grapeseed.ac (프로덕션)"
echo ""
echo "⚠️  참고: CORS 설정 변경은 최대 5분까지 소요될 수 있습니다."
echo "🔄 브라우저 캐시를 클리어하고 다시 시도해주세요."
