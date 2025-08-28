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
        Schema::create('video_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_submission_id')->constrained()->onDelete('cascade'); // 영상 제출 ID
            $table->foreignId('admin_id')->constrained()->onDelete('cascade'); // 배정된 심사위원 ID
            $table->enum('status', ['assigned', 'in_progress', 'completed'])->default('assigned'); // 배정 상태
            $table->timestamp('assigned_at')->useCurrent(); // 배정 시간
            $table->timestamp('started_at')->nullable(); // 심사 시작 시간
            $table->timestamp('completed_at')->nullable(); // 심사 완료 시간
            $table->timestamps();
            
            // 같은 심사위원이 같은 영상을 중복 배정받지 않도록 설정
            // (한 영상이 여러 심사위원에게 배정 가능)
            $table->unique(['video_submission_id', 'admin_id'], 'unique_video_admin_assignment');
            
            // 심사위원별 배정된 영상 수를 추적하기 위한 인덱스
            $table->index(['admin_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_assignments');
    }
}; 