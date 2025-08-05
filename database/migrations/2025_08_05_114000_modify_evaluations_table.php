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
        Schema::table('evaluations', function (Blueprint $table) {
            // 기존 컬럼들의 코멘트를 1-100점으로 수정
            $table->integer('pronunciation_score')->comment('정확한 발음과 자연스러운 억양, 전달력 (1-100점)')->change();
            $table->integer('vocabulary_score')->comment('올바른 어휘 및 표현 사용 (1-100점)')->change();
            $table->integer('fluency_score')->comment('유창성 수준 (1-100점)')->change();
            $table->integer('confidence_score')->comment('자신감, 긍정적이고 밝은 태도 (1-100점)')->change();
            
            // 총점 코멘트 수정 (총점 100점)
            $table->integer('total_score')->comment('총점 (자동 계산, 최대 100점)')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // 원래대로 되돌리기
            $table->integer('pronunciation_score')->comment('정확한 발음과 자연스러운 억양, 전달력 (1-10점)')->change();
            $table->integer('vocabulary_score')->comment('올바른 어휘 및 표현 사용 (1-10점)')->change();
            $table->integer('fluency_score')->comment('유창성 수준 (1-10점)')->change();
            $table->integer('confidence_score')->comment('자신감, 긍정적이고 밝은 태도 (1-10점)')->change();
            
            $table->integer('total_score')->comment('총점 (자동 계산)')->change();
        });
    }
}; 