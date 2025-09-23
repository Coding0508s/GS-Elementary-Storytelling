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
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->constrained()->onDelete('cascade');
            
            // AI 평가 점수 (각 10점 만점)
            $table->integer('pronunciation_score')->nullable(); // 정확한 발음과 자연스러운 억양 및 전달력
            $table->integer('vocabulary_score')->nullable();    // 올바른 어휘 및 표현 사용
            $table->integer('fluency_score')->nullable();       // 유창성 수준
            $table->integer('total_score')->nullable();         // 총점 (30점 만점)
            
            // Whisper 결과
            $table->text('transcription')->nullable();          // 음성을 텍스트로 변환한 결과
            $table->text('ai_feedback')->nullable();            // AI의 영어 심사평
            
            // 처리 상태 관리
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');
            $table->text('error_message')->nullable();          // 오류 발생 시 메시지
            $table->timestamp('processed_at')->nullable();      // 처리 완료 시간
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['video_submission_id', 'admin_id']);
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
    }
};
