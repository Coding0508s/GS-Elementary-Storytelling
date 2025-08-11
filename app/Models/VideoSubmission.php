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

    // 최대 파일 크기 (1GB in bytes)
    const MAX_FILE_SIZE = 1073741824; // 1GB

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
     * 영상 배정 관계
     */
    public function assignment()
    {
        return $this->hasOne(VideoAssignment::class);
    }

    /**
     * 심사 결과 관계
     */
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
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
            return Storage::disk('s3')->temporaryUrl(
                $this->video_file_path,
                now()->addHours($hours),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . $this->video_file_name . '"'
                ]
            );
        } catch (\Exception $e) {
            return null;
        }
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
