/**
 * S3 직접 업로드를 위한 JavaScript 라이브러리
 * Presigned URL을 사용하여 브라우저에서 직접 S3에 파일을 업로드합니다.
 */

class S3DirectUpload {
    constructor(options = {}) {
        this.options = {
            presignedUrlEndpoint: '/api/s3/presigned-url',
            uploadCompleteEndpoint: '/api/s3/upload-complete',
            deleteFileEndpoint: '/api/s3/delete-file',
            maxFileSize: 2 * 1024 * 1024 * 1024, // 2GB
            allowedTypes: ['video/mp4', 'video/quicktime', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/webm', 'video/mkv'],
            chunkSize: 5 * 1024 * 1024, // 5MB 청크 크기
            ...options
        };
        
        this.uploadQueue = [];
        this.activeUploads = new Map();
    }

    /**
     * 파일 업로드 시작
     */
    async uploadFile(file, onProgress = null, onComplete = null, onError = null, userInfo = null) {
        try {
            // 파일 검증
            this.validateFile(file);

            // Presigned URL 요청 (사용자 정보 포함)
            const presignedData = await this.getPresignedUrl(file, userInfo);
            
            // S3에 직접 업로드
            const uploadResult = await this.uploadToS3(file, presignedData, onProgress);
            
            // 업로드 완료 알림
            const completeResult = await this.notifyUploadComplete(presignedData.s3_key, file);
            
            if (onComplete) {
                onComplete({
                    ...uploadResult,
                    ...completeResult,
                    file: file
                });
            }
            
            return {
                success: true,
                s3_key: presignedData.s3_key,
                url: completeResult.file_info.url,
                ...completeResult
            };

        } catch (error) {
            console.error('S3 업로드 실패:', error);
            if (onError) {
                onError(error);
            }
            throw error;
        }
    }

    /**
     * 파일 검증
     */
    validateFile(file) {
        // 파일 크기 검증
        if (file.size > this.options.maxFileSize) {
            throw new Error(`파일 크기가 너무 큽니다. 최대 ${this.formatFileSize(this.options.maxFileSize)}까지 허용됩니다.`);
        }

        // 파일 타입 검증
        if (!this.options.allowedTypes.includes(file.type)) {
            throw new Error('지원하지 않는 파일 형식입니다. (MP4, AVI, MOV, WMV, FLV, WEBM, MKV만 허용)');
        }

        // 파일명 검증
        if (!file.name || file.name.trim() === '') {
            throw new Error('유효하지 않은 파일명입니다.');
        }
    }

    /**
     * Presigned URL 요청
     */
    async getPresignedUrl(file, userInfo = null) {
        const requestData = {
            filename: file.name,
            content_type: file.type,
            file_size: file.size
        };

        // 사용자 정보가 있으면 추가
        if (userInfo) {
            requestData.institution_name = userInfo.institution_name;
            requestData.student_name_korean = userInfo.student_name_korean;
            requestData.grade = userInfo.grade;
        }

        const response = await fetch(this.options.presignedUrlEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Presigned URL 생성에 실패했습니다.');
        }

        return await response.json();
    }

    /**
     * S3에 직접 업로드
     */
    async uploadToS3(file, presignedData, onProgress = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            
            // 진행률 추적
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable && onProgress) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    onProgress({
                        loaded: event.loaded,
                        total: event.total,
                        percent: percentComplete
                    });
                }
            });

            // 업로드 완료
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve({
                        success: true,
                        status: xhr.status,
                        response: xhr.response
                    });
                } else {
                    reject(new Error(`업로드 실패: HTTP ${xhr.status}`));
                }
            });

            // 업로드 오류
            xhr.addEventListener('error', () => {
                reject(new Error('네트워크 오류로 업로드에 실패했습니다.'));
            });

            // 업로드 중단
            xhr.addEventListener('abort', () => {
                reject(new Error('업로드가 중단되었습니다.'));
            });

            // PUT 요청으로 S3에 업로드
            xhr.open('PUT', presignedData.presigned_url);
            xhr.setRequestHeader('Content-Type', file.type);
            xhr.send(file);
        });
    }

    /**
     * 업로드 완료 알림
     */
    async notifyUploadComplete(s3Key, file) {
        const response = await fetch(this.options.uploadCompleteEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                s3_key: s3Key,
                original_filename: file.name,
                file_size: file.size,
                content_type: file.type
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || '업로드 완료 처리에 실패했습니다.');
        }

        return await response.json();
    }

    /**
     * S3 파일 삭제
     */
    async deleteFile(s3Key) {
        const response = await fetch(this.options.deleteFileEndpoint, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                s3_key: s3Key
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || '파일 삭제에 실패했습니다.');
        }

        return await response.json();
    }

    /**
     * 파일 크기 포맷팅
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * 업로드 취소
     */
    cancelUpload(uploadId) {
        if (this.activeUploads.has(uploadId)) {
            const xhr = this.activeUploads.get(uploadId);
            xhr.abort();
            this.activeUploads.delete(uploadId);
        }
    }

    /**
     * 모든 업로드 취소
     */
    cancelAllUploads() {
        this.activeUploads.forEach((xhr, uploadId) => {
            xhr.abort();
        });
        this.activeUploads.clear();
    }
}

// 전역에서 사용할 수 있도록 window 객체에 추가
window.S3DirectUpload = S3DirectUpload;
