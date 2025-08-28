<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // 기관 유형 상수
    const TYPES = [
        'elementary' => '초등학교',
        'middle' => '중학교',
        'high' => '고등학교',
        'academy' => '학원',
        'kindergarten' => '유치원',
        'university' => '대학교',
        'other' => '기타'
    ];

    /**
     * 활성화된 기관만 조회하는 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 정렬 순서와 이름순으로 정렬하는 스코프
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * 기관명으로 검색하는 스코프
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', '%' . $term . '%');
    }

    /**
     * 해당 기관의 영상 제출 수
     */
    public function videoSubmissions()
    {
        return $this->hasMany(VideoSubmission::class, 'institution_name', 'name');
    }
}
