<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_submission_id',
        'admin_id',
        'pronunciation_score',
        'vocabulary_score',
        'fluency_score',
        'confidence_score',
        'total_score',
        'comments'
    ];

    protected $casts = [
        'pronunciation_score' => 'integer',
        'vocabulary_score' => 'integer',
        'fluency_score' => 'integer',
        'confidence_score' => 'integer',
        'total_score' => 'integer'
    ];

    /**
     * 총점 자동 계산
     */
    public function calculateTotalScore()
    {
        $this->total_score = $this->pronunciation_score + 
                           $this->vocabulary_score + 
                           $this->fluency_score + 
                           $this->confidence_score;
        return $this->total_score;
    }

    /**
     * 저장 전에 총점 자동 계산
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            $evaluation->calculateTotalScore();
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
     * 점수 검증 규칙
     */
    public static function validationRules()
    {
        return [
            'video_submission_id' => 'required|exists:video_submissions,id',
            'pronunciation_score' => 'required|integer|min:1|max:10',
            'vocabulary_score' => 'required|integer|min:1|max:10',
            'fluency_score' => 'required|integer|min:1|max:10',
            'confidence_score' => 'required|integer|min:1|max:10',
            'comments' => 'nullable|string|max:1000'
        ];
    }

    /**
     * 평가 기준 라벨
     */
    public static function getCriteriaLabels()
    {
        return [
            'pronunciation_score' => '정확한 발음과 자연스러운 억양, 전달력',
            'vocabulary_score' => '올바른 어휘 및 표현 사용',
            'fluency_score' => '유창성 수준',
            'confidence_score' => '자신감, 긍정적이고 밝은 태도'
        ];
    }
}
