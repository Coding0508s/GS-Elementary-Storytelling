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
        'topic_connection_score',
        'structure_flow_score',
        'creativity_score',
        'total_score',
        'comments',
        // 2차 예선진출 기능이 필요 없어서 주석처리
        // 'qualification_status',
        // 'rank_by_judge',
        // 'qualified_at'
    ];

    protected $casts = [
        'pronunciation_score' => 'integer',
        'vocabulary_score' => 'integer',
        'fluency_score' => 'integer',
        'confidence_score' => 'integer',
        'topic_connection_score' => 'integer',
        'structure_flow_score' => 'integer',
        'creativity_score' => 'integer',
        'total_score' => 'integer',
        // 2차 예선진출 기능이 필요 없어서 주석처리
        // 'rank_by_judge' => 'integer',
        // 'qualified_at' => 'datetime'
    ];

    // 자격 상태 상수 정의 (2차 예선진출 기능이 필요 없어서 주석처리)
    /*
    const QUALIFICATION_PENDING = 'pending';
    const QUALIFICATION_QUALIFIED = 'qualified';
    const QUALIFICATION_NOT_QUALIFIED = 'not_qualified';
    */

    /**
     * 총점 자동 계산 (7개 항목 × 10점 = 70점 만점)
     */
    public function calculateTotalScore()
    {
        $this->total_score = $this->pronunciation_score + 
                           $this->vocabulary_score + 
                           $this->fluency_score + 
                           $this->confidence_score +
                           $this->topic_connection_score +
                           $this->structure_flow_score +
                           $this->creativity_score;
        return $this->total_score;
    }

    /**
     * 발음 환산 점수 (가상 속성)
     */
    public function getPronunciationConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->pronunciation_score / $this->total_score) * 100, 1);
    }

    /**
     * 어휘 환산 점수 (가상 속성)
     */
    public function getVocabularyConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->vocabulary_score / $this->total_score) * 100, 1);
    }

    /**
     * 유창성 환산 점수 (가상 속성)
     */
    public function getFluencyConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->fluency_score / $this->total_score) * 100, 1);
    }

    /**
     * 자신감 환산 점수 (가상 속성)
     */
    public function getConfidenceConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->confidence_score / $this->total_score) * 100, 1);
    }

    /**
     * 주제연결성 환산 점수 (가상 속성)
     */
    public function getTopicConnectionConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->topic_connection_score / $this->total_score) * 100, 1);
    }

    /**
     * 구성·흐름 환산 점수 (가상 속성)
     */
    public function getStructureFlowConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->structure_flow_score / $this->total_score) * 100, 1);
    }

    /**
     * 창의성 환산 점수 (가상 속성)
     */
    public function getCreativityConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return round(($this->creativity_score / $this->total_score) * 100, 1);
    }

    /**
     * 총 환산 점수 (가상 속성)
     */
    public function getTotalConvertedAttribute()
    {
        if ($this->total_score == 0) return 0.0;
        return 100.0;
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
     * 영상 배정 관계
     */
    public function videoAssignment()
    {
        return $this->belongsTo(VideoAssignment::class, 'video_submission_id', 'video_submission_id')
                    ->where('video_assignments.admin_id', '=', $this->admin_id);
    }

    /**
     * 점수 검증 규칙
     */
    public static function validationRules()
    {
        return [
            'video_submission_id' => 'required|exists:video_submissions,id',
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'topic_connection_score' => 'required|integer|min:0|max:10',
            'structure_flow_score' => 'required|integer|min:0|max:10',
            'creativity_score' => 'required|integer|min:0|max:10',
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
            'confidence_score' => '자신감, 긍정적이고 밝은 태도',
            'topic_connection_score' => '주제와 발표 내용과의 연결성',
            'structure_flow_score' => '자연스러운 구성과 흐름',
            'creativity_score' => '창의적 내용'
        ];
    }
    
    /**
     * 환산 점수 필드명 매핑
     */
    public static function getConvertedFieldMapping()
    {
        return [
            'pronunciation_score' => 'pronunciation_converted',
            'vocabulary_score' => 'vocabulary_converted',
            'fluency_score' => 'fluency_converted',
            'confidence_score' => 'confidence_converted'
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
}
