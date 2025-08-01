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
     * 심사 관계 설정
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * 마지막 로그인 시간 업데이트
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}
