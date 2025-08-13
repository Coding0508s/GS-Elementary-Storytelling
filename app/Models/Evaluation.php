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
        'pronunciation_converted',
        'vocabulary_converted',
        'fluency_converted',
        'confidence_converted',
        'total_converted',
        'comments',
        'qualification_status',
        'rank_by_judge',
        'qualified_at'
    ];

    protected $casts = [
        'pronunciation_score' => 'integer',
        'vocabulary_score' => 'integer',
        'fluency_score' => 'integer',
        'confidence_score' => 'integer',
        'total_score' => 'integer',
        'pronunciation_converted' => 'decimal:1',
        'vocabulary_converted' => 'decimal:1',
        'fluency_converted' => 'decimal:1',
        'confidence_converted' => 'decimal:1',
        'total_converted' => 'decimal:1',
        'rank_by_judge' => 'integer',
        'qualified_at' => 'datetime'
    ];

    // 자격 상태 상수 정의
    const QUALIFICATION_PENDING = 'pending';
    const QUALIFICATION_QUALIFIED = 'qualified';
    const QUALIFICATION_NOT_QUALIFIED = 'not_qualified';

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
     * 환산 점수 자동 계산
     * 공식: (해당 점수 ÷ 전체 점수 합계) × 100
     */
    public function calculateConvertedScores()
    {
        $totalScore = $this->total_score;
        
        // 총점이 0이면 모든 환산 점수도 0
        if ($totalScore == 0) {
            $this->pronunciation_converted = 0.0;
            $this->vocabulary_converted = 0.0;
            $this->fluency_converted = 0.0;
            $this->confidence_converted = 0.0;
            $this->total_converted = 0.0;
            return;
        }
        
        // 각 항목의 환산 점수 계산 (소수점 1자리 반올림)
        $this->pronunciation_converted = round(($this->pronunciation_score / $totalScore) * 100, 1);
        $this->vocabulary_converted = round(($this->vocabulary_score / $totalScore) * 100, 1);
        $this->fluency_converted = round(($this->fluency_score / $totalScore) * 100, 1);
        $this->confidence_converted = round(($this->confidence_score / $totalScore) * 100, 1);
        
        // 반올림으로 인한 오차 보정 (총합이 정확히 100이 되도록)
        $convertedTotal = $this->pronunciation_converted + 
                         $this->vocabulary_converted + 
                         $this->fluency_converted + 
                         $this->confidence_converted;
        
        if ($convertedTotal != 100.0) {
            // 가장 큰 점수에 오차를 보정
            $maxField = $this->getMaxScoreField();
            $difference = 100.0 - $convertedTotal;
            $this->{$maxField . '_converted'} = round($this->{$maxField . '_converted'} + $difference, 1);
        }
        
        $this->total_converted = 100.0;
    }
    
    /**
     * 가장 높은 점수를 받은 항목 찾기 (오차 보정용)
     */
    private function getMaxScoreField()
    {
        $scores = [
            'pronunciation' => $this->pronunciation_score,
            'vocabulary' => $this->vocabulary_score,
            'fluency' => $this->fluency_score,
            'confidence' => $this->confidence_score
        ];
        
        return array_keys($scores, max($scores))[0];
    }

    /**
     * 저장 전에 총점과 환산 점수 자동 계산
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            $evaluation->calculateTotalScore();
            $evaluation->calculateConvertedScores();
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
