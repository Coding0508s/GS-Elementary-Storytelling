<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_submission_id',
        'admin_id',
        'status',
        'assigned_at',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // 상태 상수
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    /**
     * 영상 제출과의 관계
     */
    public function videoSubmission()
    {
        return $this->belongsTo(VideoSubmission::class);
    }

    /**
     * 심사위원과의 관계
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * 심사와의 관계 (해당 심사위원의 평가만)
     */
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class, 'video_submission_id', 'video_submission_id')
                    ->where('evaluations.admin_id', $this->admin_id);
    }

    /**
     * 배정 상태가 'assigned'인지 확인
     */
    public function isAssigned()
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    /**
     * 배정 상태가 'in_progress'인지 확인
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * 배정 상태가 'completed'인지 확인
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 심사 시작
     */
    public function startEvaluation()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now()
        ]);
    }

    /**
     * 심사 완료
     */
    public function completeEvaluation()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }
} 