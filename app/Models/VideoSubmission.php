<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class VideoSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'institution_name',
        'class_name', 
        'student_name_korean',
        'student_name_english',
        'grade',
        'age',
        'parent_name',
        'parent_phone',
        'video_file_path',
        'video_file_name',
        'video_file_type',
        'video_file_size',
        'unit_topic',
        'privacy_consent',
        'privacy_consent_at',
        'notification_sent',
        'notification_sent_at',
        'status'
    ];

    protected $casts = [
        'age' => 'integer',
        'video_file_size' => 'integer',
        'privacy_consent' => 'boolean',
        'notification_sent' => 'boolean',
        'privacy_consent_at' => 'datetime',
        'notification_sent_at' => 'datetime'
    ];

    // 상태 상수 정의
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // 허용된 파일 형식
    const ALLOWED_FILE_TYPES = ['mp4', 'mov'];

    // 최대 파일 크기 (2GB in bytes)
    const MAX_FILE_SIZE = 2147483648; // 2GB

    // 한국 지역 목록 (시/도별 시/군/구 포함)
    const REGIONS = [
        '서울특별시' => [
            '강남구', '강동구', '강북구', '강서구', '관악구', '광진구', '구로구', '금천구',
            '노원구', '도봉구', '동대문구', '동작구', '마포구', '서대문구', '서초구', '성동구',
            '성북구', '송파구', '양천구', '영등포구', '용산구', '은평구', '종로구', '중구', '중랑구'
        ],
        '부산광역시' => [
            '강서구', '금정구', '기장군', '남구', '동구', '동래구', '부산진구', '북구',
            '사상구', '사하구', '서구', '수영구', '연제구', '영도구', '중구', '해운대구'
        ],
        '대구광역시' => [
            '남구', '달서구', '달성군', '동구', '북구', '서구', '수성구', '중구'
        ],
        '인천광역시' => [
            '강화군', '계양구', '남동구', '동구', '미추홀구', '부평구', '서구', '연수구', '옹진군', '중구'
        ],
        '광주광역시' => [
            '광산구', '남구', '동구', '북구', '서구'
        ],
        '대전광역시' => [
            '대덕구', '동구', '서구', '유성구', '중구'
        ],
        '울산광역시' => [
            '남구', '동구', '북구', '울주군', '중구'
        ],
        '세종특별자치시' => [
            '세종시'
        ],
        '경기도' => [
            '가평군', '고양시', '과천시', '광명시', '광주시', '구리시', '군포시', '김포시',
            '남양주시', '동두천시', '부천시', '성남시', '수원시', '시흥시', '안산시', '안성시',
            '안양시', '양주시', '양평군', '여주시', '연천군', '오산시', '용인시', '의왕시',
            '의정부시', '이천시', '파주시', '평택시', '포천시', '하남시', '화성시'
        ],
        '강원도' => [
            '강릉시', '고성군', '동해시', '삼척시', '속초시', '양구군', '양양군', '영월군',
            '원주시', '인제군', '정선군', '철원군', '춘천시', '태백시', '평창군', '홍천군', '화천군', '횡성군'
        ],
        '충청북도' => [
            '괴산군', '단양군', '보은군', '영동군', '옥천군', '음성군', '증평군', '진천군',
            '청주시', '충주시', '제천시'
        ],
        '충청남도' => [
            '계룡시', '공주시', '금산군', '논산시', '당진시', '보령시', '부여군', '서산시',
            '서천군', '아산시', '예산군', '천안시', '청양군', '태안군', '홍성군'
        ],
        '전라북도' => [
            '고창군', '군산시', '김제시', '남원시', '무주군', '부안군', '순창군', '완주군',
            '익산시', '임실군', '장수군', '전주시', '정읍시', '진안군'
        ],
        '전라남도' => [
            '강진군', '고흥군', '곡성군', '광양시', '구례군', '나주시', '담양군', '목포시',
            '무안군', '보성군', '순천시', '신안군', '여수시', '영광군', '영암군', '완도군',
            '장성군', '장흥군', '진도군', '함평군', '해남군', '화순군'
        ],
        '경상북도' => [
            '경산시', '경주시', '고령군', '구미시', '군위군', '김천시', '문경시', '봉화군',
            '상주시', '성주군', '안동시', '영덕군', '영양군', '영주시', '영천시', '예천군',
            '울릉군', '울진군', '의성군', '청도군', '청송군', '칠곡군', '포항시'
        ],
        '경상남도' => [
            '거제시', '거창군', '고성군', '김해시', '남해군', '밀양시', '사천시', '산청군',
            '양산시', '의령군', '진주시', '창녕군', '창원시', '통영시', '하동군', '함안군',
            '함양군', '합천군'
        ],
        '제주특별자치도' => [
            '서귀포시', '제주시'
        ],
        '해외' => [
            '해외'
        ]
    ];

    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->video_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 업로드 완료 여부 확인
     */
    public function isUploadCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 알림 발송 필요 여부 확인
     */
    public function needsNotification()
    {
        return $this->isUploadCompleted() && !$this->notification_sent;
    }

    /**
     * 영상 배정 관계 (단수)
     */
    public function assignment()
    {
        return $this->hasOne(VideoAssignment::class);
    }
    
    /**
     * 영상 배정 관계 (복수 - 최대 2명의 심사위원)
     */
    public function assignments()
    {
        return $this->hasMany(VideoAssignment::class);
    }

    /**
     * 심사 결과 관계 (단수)
     */
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }
    
    /**
     * 심사 결과 관계 (복수 - 최대 2명의 심사위원)
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * 영상 파일 URL 가져오기 (S3 임시 URL)
     */
    public function getVideoUrlAttribute()
    {
        if ($this->isStoredOnS3()) {
            return $this->getS3TemporaryUrl();
        }
        return asset('storage/' . $this->video_file_path);
    }

    /**
     * 영상 파일이 S3에 저장되어 있는지 확인
     */
    public function isStoredOnS3()
    {
        return config('filesystems.default') === 's3' && $this->video_file_path;
    }

    /**
     * 영상 파일이 로컬에 저장되어 있는지 확인
     */
    public function isStoredLocally()
    {
        return config('filesystems.default') !== 's3' && $this->video_file_path;
    }

    /**
     * 로컬 스토리지에서 영상 URL 가져오기
     */
    public function getLocalVideoUrl()
    {
        if (!$this->isStoredLocally()) {
            return null;
        }
        
        // public/storage/videos/filename.mp4 형태로 접근
        return asset('storage/' . $this->video_file_path);
    }

    /**
     * S3에서 임시 URL 생성 (1시간 유효)
     */
    public function getS3TemporaryUrl($hours = 1)
    {
        if (!$this->isStoredOnS3()) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->video_file_path,
                now()->addHours($hours)
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * S3에서 다운로드용 임시 URL 생성 (강제 다운로드)
     */
    public function getS3DownloadUrl($hours = 1)
    {
        if (!$this->isStoredOnS3()) {
            return null;
        }

        try {
            // 한글 파일명을 안전하게 처리
            $originalFilename = $this->video_file_name;
            
            // 파일명에서 특수문자 제거 및 정리
            $safeFilename = preg_replace('/[^\w\-_.가-힣a-zA-Z0-9]/', '_', $originalFilename);
            
            // UTF-8 인코딩 (percent encoding)
            $encodedFilename = rawurlencode($safeFilename);
            
            // RFC 6266 표준에 따른 Content-Disposition 헤더
            // filename*=UTF-8''encoded_filename 형식 사용
            $contentDisposition = "attachment; filename*=UTF-8''" . $encodedFilename;
            
            return Storage::disk('s3')->temporaryUrl(
                $this->video_file_path,
                now()->addHours($hours),
                [
                    'ResponseContentDisposition' => $contentDisposition
                ]
            );
        } catch (\Exception $e) {
            \Log::error('S3 다운로드 URL 생성 실패: ' . $e->getMessage(), [
                'video_id' => $this->id,
                'filename' => $this->video_file_name
            ]);
            return null;
        }
    }

    /**
     * S3에서 안전한 다운로드용 임시 URL 생성 (대안 방법)
     */
    public function getSafeS3DownloadUrl($hours = 1)
    {
        if (!$this->isStoredOnS3()) {
            return null;
        }

        try {
            // 영어/숫자로만 구성된 안전한 파일명 생성
            $timestamp = date('YmdHis');
            $extension = pathinfo($this->video_file_name, PATHINFO_EXTENSION);
            $safeFilename = 'video_' . $this->id . '_' . $timestamp . '.' . $extension;
            
            return Storage::disk('s3')->temporaryUrl(
                $this->video_file_path,
                now()->addHours($hours),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . $safeFilename . '"'
                ]
            );
        } catch (\Exception $e) {
            \Log::error('안전한 S3 다운로드 URL 생성 실패: ' . $e->getMessage(), [
                'video_id' => $this->id,
                'filename' => $this->video_file_name
            ]);
            return null;
        }
    }

    /**
     * 접수번호 생성
     */
    public function getReceiptNumber()
    {
        return 'GSK-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * 접수번호 속성 (Accessor)
     */
    public function getReceiptNumberAttribute()
    {
        return $this->getReceiptNumber();
    }

    /**
     * 배정된 심사위원 가져오기
     */
    public function getAssignedAdmin()
    {
        return $this->assignment ? $this->assignment->admin : null;
    }

    /**
     * 배정 상태 확인
     */
    public function isAssigned()
    {
        return $this->assignment !== null;
    }

    /**
     * 심사 완료 여부 확인
     */
    public function isEvaluated()
    {
        return $this->evaluation !== null;
    }

    /**
     * 심사 점수 가져오기
     */
    public function getTotalScore()
    {
        return $this->evaluation ? $this->evaluation->total_score : null;
    }

    /**
     * 배정 상태 가져오기
     */
    public function getAssignmentStatus()
    {
        return $this->assignment ? $this->assignment->status : null;
    }
}
