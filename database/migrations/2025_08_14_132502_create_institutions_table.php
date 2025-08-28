<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 기관명 (중복 방지)
            $table->string('type')->nullable(); // 기관 유형 (초등학교, 중학교, 학원, 유치원 등)
            $table->text('description')->nullable(); // 기관 설명
            $table->boolean('is_active')->default(true); // 활성화 여부
            $table->integer('sort_order')->default(0); // 정렬 순서
            $table->timestamps();
            
            // 인덱스
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
