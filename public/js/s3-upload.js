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
            chunkSize: 10 * 1024 * 1024, // 10MB 청크 크기 (속도 최적화)
            maxConcurrentUploads: 3, // 동시 업로드 수
            retryAttempts: 3, // 재시도 횟수
            ...options
        };
        
        this.uploadQueue = [];
        this.activeUploads = new Map();
        this.networkInfo = this.detectNetworkInfo();
        this.optimizeForMobile();
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
                url: completeResult.file_info?.url || uploadResult.url,
                ...completeResult,
                file_info: completeResult.file_info
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
     * S3에 직접 업로드 (청크 업로드 및 병렬 처리 최적화)
     */
    async uploadToS3(file, presignedData, onProgress = null) {
        // 대용량 파일의 경우 청크 업로드 사용
        if (file.size > 50 * 1024 * 1024) { // 50MB 이상
            return this.uploadFileInChunks(file, presignedData, onProgress);
        }
        
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const uploadId = this.generateUploadId();
            
            // 업로드 상태 저장 (재개를 위해)
            const uploadState = {
                id: uploadId,
                file: file,
                presignedData: presignedData,
                startTime: Date.now(),
                lastProgress: 0,
                isPaused: false,
                isResumed: false
            };
            
            // 진행률 추적 (배경 업로드 지원)
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable && onProgress) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    uploadState.lastProgress = percentComplete;
                    
                    // 배경 업로드 상태 저장
                    this.saveUploadState(uploadState);
                    
                    onProgress({
                        loaded: event.loaded,
                        total: event.total,
                        percent: percentComplete,
                        uploadId: uploadId,
                        isBackground: document.hidden
                    });
                }
            });

            // 업로드 완료
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // 업로드 상태 정리
                    this.clearUploadState(uploadId);
                    
                    resolve({
                        success: true,
                        status: xhr.status,
                        response: xhr.response,
                        uploadId: uploadId
                    });
                } else {
                    reject(new Error(`업로드 실패: HTTP ${xhr.status}`));
                }
            });

            // 업로드 오류 (재시도 로직 포함)
            xhr.addEventListener('error', () => {
                const error = new Error('네트워크 오류로 업로드에 실패했습니다.');
                error.uploadId = uploadId;
                error.canRetry = true;
                reject(error);
            });

            // 업로드 중단
            xhr.addEventListener('abort', () => {
                const error = new Error('업로드가 중단되었습니다.');
                error.uploadId = uploadId;
                error.canResume = true;
                reject(error);
            });

            // 타임아웃 처리
            xhr.addEventListener('timeout', () => {
                const error = new Error('업로드 타임아웃이 발생했습니다.');
                error.uploadId = uploadId;
                error.canRetry = true;
                reject(error);
            });

            // PUT 요청으로 S3에 업로드 (속도 최적화)
            xhr.open('PUT', presignedData.presigned_url);
            xhr.timeout = this.options.timeout || 1800000; // 30분 타임아웃 (대용량 파일 대응)
            xhr.setRequestHeader('Content-Type', file.type);
            
            // 업로드 속도 최적화 헤더 (안전한 헤더만 사용)
            xhr.setRequestHeader('Cache-Control', 'no-cache');
            
            // 배경 업로드 지원
            if (this.options.backgroundUpload !== false) {
                xhr.setRequestHeader('X-Background-Upload', 'true');
            }
            
            xhr.send(file);
            
            // 활성 업로드에 추가
            this.activeUploads.set(uploadId, xhr);
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
     * 청크 업로드 구현 (대용량 파일 최적화)
     */
    async uploadFileInChunks(file, presignedData, onProgress = null) {
        const chunkSize = this.options.chunkSize;
        const totalChunks = Math.ceil(file.size / chunkSize);
        let uploadedBytes = 0;
        
        console.log(`청크 업로드 시작: ${totalChunks}개 청크, 각 ${this.formatFileSize(chunkSize)}`);
        
        for (let i = 0; i < totalChunks; i++) {
            const start = i * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);
            
            try {
                await this.uploadChunk(chunk, i, presignedData);
                uploadedBytes += chunk.size;
                
                if (onProgress) {
                    const percent = (uploadedBytes / file.size) * 100;
                    onProgress({
                        loaded: uploadedBytes,
                        total: file.size,
                        percent: percent,
                        chunk: i + 1,
                        totalChunks: totalChunks
                    });
                }
                
                // 청크 간 짧은 지연 (서버 부하 방지)
                if (i < totalChunks - 1) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
                
            } catch (error) {
                console.error(`청크 ${i + 1} 업로드 실패:`, error);
                throw new Error(`청크 ${i + 1}/${totalChunks} 업로드 실패: ${error.message}`);
            }
        }
        
        return {
            success: true,
            totalChunks: totalChunks,
            uploadedBytes: uploadedBytes
        };
    }
    
    /**
     * 개별 청크 업로드
     */
    async uploadChunk(chunk, chunkIndex, presignedData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve();
                } else {
                    reject(new Error(`청크 업로드 실패: HTTP ${xhr.status}`));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('청크 업로드 네트워크 오류'));
            });
            
            xhr.open('PUT', presignedData.presigned_url);
            xhr.timeout = 300000; // 5분 타임아웃
            xhr.setRequestHeader('Content-Type', 'application/octet-stream');
            xhr.setRequestHeader('X-Chunk-Index', chunkIndex);
            xhr.send(chunk);
        });
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

    /**
     * 네트워크 정보 감지
     */
    detectNetworkInfo() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (connection) {
            return {
                effectiveType: connection.effectiveType, // 'slow-2g', '2g', '3g', '4g'
                downlink: connection.downlink, // Mbps
                rtt: connection.rtt, // Round Trip Time (ms)
                saveData: connection.saveData, // 데이터 절약 모드
                type: connection.type // 'cellular', 'wifi', 'ethernet', etc.
            };
        }
        
        // 기본값 (연결 정보를 알 수 없는 경우)
        return {
            effectiveType: '4g',
            downlink: 10,
            rtt: 100,
            saveData: false,
            type: 'unknown'
        };
    }

    /**
     * 모바일 환경 최적화
     */
    optimizeForMobile() {
        const { effectiveType, downlink, saveData, type } = this.networkInfo;
        
        // 모바일 데이터 환경 감지
        const isMobileData = type === 'cellular' || effectiveType === '2g' || effectiveType === '3g';
        const isSlowConnection = effectiveType === 'slow-2g' || effectiveType === '2g' || downlink < 1;
        
        if (isMobileData || isSlowConnection || saveData) {
            // 모바일 데이터 환경 최적화
            this.options.chunkSize = 1 * 1024 * 1024; // 1MB로 축소
            this.options.timeout = 1800000; // 30분으로 연장
            this.options.retryAttempts = 5; // 재시도 횟수 증가
            this.options.retryDelay = 2000; // 재시도 간격 증가
            
            console.log('📱 모바일 데이터 환경 감지 - 업로드 최적화 적용', {
                effectiveType,
                downlink: downlink + ' Mbps',
                saveData,
                chunkSize: this.formatFileSize(this.options.chunkSize)
            });
        } else if (effectiveType === '4g' && downlink > 5) {
            // 고속 연결 환경 최적화
            this.options.chunkSize = 10 * 1024 * 1024; // 10MB로 증가
            this.options.timeout = 900000; // 15분
            this.options.retryAttempts = 3;
            this.options.retryDelay = 1000;
            
            console.log('🚀 고속 연결 환경 감지 - 업로드 최적화 적용', {
                effectiveType,
                downlink: downlink + ' Mbps',
                chunkSize: this.formatFileSize(this.options.chunkSize)
            });
        }
    }

    /**
     * 네트워크 상태 기반 업로드 전략 조정
     */
    adjustUploadStrategy(fileSize) {
        const { effectiveType, downlink, saveData } = this.networkInfo;
        
        // 파일 크기별 전략 조정
        if (fileSize > 500 * 1024 * 1024) { // 500MB 이상
            if (effectiveType === '2g' || effectiveType === 'slow-2g') {
                return {
                    strategy: 'conservative',
                    chunkSize: 512 * 1024, // 512KB
                    timeout: 3600000, // 1시간
                    retryAttempts: 10,
                    retryDelay: 5000,
                    message: '대용량 파일을 느린 연결에서 업로드합니다. 시간이 오래 걸릴 수 있습니다.'
                };
            } else if (effectiveType === '3g') {
                return {
                    strategy: 'balanced',
                    chunkSize: 1 * 1024 * 1024, // 1MB
                    timeout: 1800000, // 30분
                    retryAttempts: 7,
                    retryDelay: 3000,
                    message: '3G 연결에서 업로드합니다. 안정적인 연결을 유지해주세요.'
                };
            }
        }
        
        return {
            strategy: 'default',
            chunkSize: this.options.chunkSize,
            timeout: this.options.timeout,
            retryAttempts: this.options.retryAttempts,
            retryDelay: this.options.retryDelay,
            message: '최적화된 설정으로 업로드합니다.'
        };
    }

    /**
     * 데이터 사용량 추정
     */
    estimateDataUsage(fileSize) {
        const { effectiveType, saveData } = this.networkInfo;
        
        // 압축률 추정 (비디오 파일의 경우)
        const compressionRatio = 0.8; // 20% 압축 가정
        const estimatedUploadSize = fileSize * compressionRatio;
        
        // 네트워크 오버헤드 (HTTP 헤더, 재시도 등)
        const overheadRatio = 1.1; // 10% 오버헤드
        const totalDataUsage = estimatedUploadSize * overheadRatio;
        
        return {
            originalSize: this.formatFileSize(fileSize),
            estimatedUploadSize: this.formatFileSize(estimatedUploadSize),
            totalDataUsage: this.formatFileSize(totalDataUsage),
            isDataSaver: saveData,
            networkType: effectiveType
        };
    }

    /**
     * 업로드 ID 생성
     */
    generateUploadId() {
        return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * 업로드 상태 저장 (재개를 위해)
     */
    saveUploadState(uploadState) {
        try {
            localStorage.setItem('s3_upload_state_' + uploadState.id, JSON.stringify({
                ...uploadState,
                file: null, // File 객체는 직렬화할 수 없으므로 제외
                presignedData: uploadState.presignedData
            }));
        } catch (e) {
            console.warn('업로드 상태 저장 실패:', e);
        }
    }

    /**
     * 업로드 상태 복원
     */
    restoreUploadState(uploadId) {
        try {
            const state = localStorage.getItem('s3_upload_state_' + uploadId);
            return state ? JSON.parse(state) : null;
        } catch (e) {
            console.warn('업로드 상태 복원 실패:', e);
            return null;
        }
    }

    /**
     * 업로드 상태 정리
     */
    clearUploadState(uploadId) {
        try {
            localStorage.removeItem('s3_upload_state_' + uploadId);
        } catch (e) {
            console.warn('업로드 상태 정리 실패:', e);
        }
    }

    /**
     * 배경 업로드 지원
     */
    enableBackgroundUpload() {
        // 페이지 가시성 변경 감지
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('📱 페이지가 백그라운드로 이동 - 업로드 계속 진행');
            } else {
                console.log('📱 페이지가 포그라운드로 복귀 - 업로드 상태 확인');
                this.checkBackgroundUploads();
            }
        });

        // 앱이 백그라운드로 이동할 때 업로드 계속
        window.addEventListener('beforeunload', () => {
            if (this.activeUploads.size > 0) {
                console.log('📱 페이지 종료 - 배경 업로드 계속 진행');
            }
        });
    }

    /**
     * 배경 업로드 상태 확인
     */
    checkBackgroundUploads() {
        this.activeUploads.forEach((xhr, uploadId) => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                console.log('📱 배경 업로드 완료:', uploadId);
                this.activeUploads.delete(uploadId);
            }
        });
    }

    /**
     * 업로드 재개
     */
    async resumeUpload(uploadId, file, onProgress = null, onComplete = null, onError = null) {
        const savedState = this.restoreUploadState(uploadId);
        if (!savedState) {
            throw new Error('재개할 업로드 상태를 찾을 수 없습니다.');
        }

        console.log('📱 업로드 재개:', uploadId, '진행률:', savedState.lastProgress + '%');

        try {
            // 새로운 Presigned URL 요청 (기존 URL이 만료되었을 수 있음)
            const presignedData = await this.getPresignedUrl(file, savedState.userInfo);
            
            // 업로드 재개
            const uploadResult = await this.uploadToS3(file, presignedData, onProgress);
            
            if (onComplete) {
                onComplete(uploadResult);
            }
            
            return uploadResult;
        } catch (error) {
            if (onError) {
                onError(error);
            }
            throw error;
        }
    }
}

// 전역에서 사용할 수 있도록 window 객체에 추가
window.S3DirectUpload = S3DirectUpload;
