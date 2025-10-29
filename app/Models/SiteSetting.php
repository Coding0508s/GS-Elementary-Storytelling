<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    /**
     * 설정 값을 가져오는 정적 메서드
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * 설정 값을 설정하는 정적 메서드
     */
    public static function set($key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }

    /**
     * 대회 활성화 상태 확인
     */
    public static function isContestActive()
    {
        return self::get('contest_active', 'true') === 'true';
    }

    /**
     * 대회 활성화 상태 토글
     */
    public static function toggleContest()
    {
        $current = self::isContestActive();
        $newValue = $current ? 'false' : 'true';
        return self::set('contest_active', $newValue, '대회 페이지 활성화 상태');
    }
}
