<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_submission_id',
        'admin_id',
        'pronunciation_score',
        'vocabulary_score',
        'fluency_score',
        'total_score',
        'transcription',
        'ai_feedback',
        'processing_status',
        'error_message',
        'processed_at'
    ];

    protected $casts = [
        'pronunciation_score' => 'integer',
        'vocabulary_score' => 'integer',
        'fluency_score' => 'integer',
        'total_score' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // 처리 상태 상수 정의
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * 총점 자동 계산
     */
    public function calculateTotalScore()
    {
        $this->total_score = $this->pronunciation_score + 
                           $this->vocabulary_score + 
                           $this->fluency_score;
        return $this->total_score;
    }

    /**
     * 처리 완료 여부 확인
     */
    public function isCompleted()
    {
        return $this->processing_status === self::STATUS_COMPLETED;
    }

    /**
     * 처리 실패 여부 확인
     */
    public function isFailed()
    {
        return $this->processing_status === self::STATUS_FAILED;
    }

    /**
     * 처리 중 여부 확인
     */
    public function isProcessing()
    {
        return $this->processing_status === self::STATUS_PROCESSING;
    }

    /**
     * 저장 전에 총점 자동 계산
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($aiEvaluation) {
            if ($aiEvaluation->pronunciation_score && 
                $aiEvaluation->vocabulary_score && 
                $aiEvaluation->fluency_score) {
                $aiEvaluation->calculateTotalScore();
            }
        });
    }

    /**
     * 영상 제출 관계
     */
    public function videoSubmission()
    {
        return $this->belongsTo(VideoSubmission::class);
    }

    /**
     * 관리자 관계
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * AI 평가 기준 라벨
     */
    public static function getCriteriaLabels()
    {
        return [
            'pronunciation_score' => '정확한 발음과 자연스러운 억양 및 전달력',
            'vocabulary_score' => '올바른 어휘 및 표현 사용',
            'fluency_score' => '유창성 수준'
        ];
    }

    /**
     * 점수 가이드 (0~10점 기준)
     */
    public static function getScoreGuide()
    {
        return [
            '0-2' => '매우 미흡',
            '3-4' => '미흡',
            '5-6' => '보통',
            '7-8' => '양호',
            '9-10' => '우수'
        ];
    }

    /**
     * 처리 상태 라벨
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => '대기 중',
            self::STATUS_PROCESSING => '처리 중',
            self::STATUS_COMPLETED => '완료',
            self::STATUS_FAILED => '실패'
        ];
    }

    /**
     * 상태 라벨 가져오기
     */
    public function getStatusLabel()
    {
        return self::getStatusLabels()[$this->processing_status] ?? '알 수 없음';
    }
}
