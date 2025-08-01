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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_submission_id')->constrained()->onDelete('cascade'); // 영상 제출 ID
            $table->foreignId('admin_id')->constrained()->onDelete('cascade'); // 심사한 관리자 ID
            
            // 심사 기준별 점수 (1-10점)
            $table->integer('pronunciation_score')->comment('정확한 발음과 자연스러운 억양, 전달력 (1-10점)');
            $table->integer('vocabulary_score')->comment('올바른 어휘 및 표현 사용 (1-10점)');
            $table->integer('fluency_score')->comment('유창성 수준 (1-10점)');
            $table->integer('confidence_score')->comment('자신감, 긍정적이고 밝은 태도 (1-10점)');
            
            $table->integer('total_score')->comment('총점 (자동 계산)');
            $table->text('comments')->nullable()->comment('심사 코멘트');
            
            $table->timestamps();
            
            // 하나의 영상에 대해 하나의 심사만 가능하도록 유니크 제약 조건
            $table->unique(['video_submission_id', 'admin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
