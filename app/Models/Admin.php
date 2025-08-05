<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'name',
        'email',
        'role',
        'is_active',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime'
    ];

    /**
     * 비밀번호를 자동으로 해시화
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * 로그인 시 사용할 username 필드 지정
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * 영상 배정 관계
     */
    public function videoAssignments()
    {
        return $this->hasMany(VideoAssignment::class);
    }

    /**
     * 심사 관계 설정
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * 배정된 영상 수 가져오기
     */
    public function getAssignedVideoCount()
    {
        return $this->videoAssignments()->count();
    }

    /**
     * 완료된 심사 수 가져오기
     */
    public function getCompletedEvaluationCount()
    {
        return $this->evaluations()->count();
    }

    /**
     * 진행 중인 배정 수 가져오기
     */
    public function getInProgressAssignmentCount()
    {
        return $this->videoAssignments()->where('status', VideoAssignment::STATUS_IN_PROGRESS)->count();
    }

    /**
     * 마지막 로그인 시간 업데이트
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * 관리자인지 확인
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * 심사위원인지 확인
     */
    public function isJudge()
    {
        return $this->role === 'judge';
    }
}
