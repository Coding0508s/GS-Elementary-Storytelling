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
        // MySQL에서 외래키 제약조건으로 인한 문제를 해결하기 위해
        // 생성 단계에서는 올바른 제약조건을 설정하고
        // 기존 데이터가 있는 경우에만 수정을 시도합니다
        
        $tableExists = Schema::hasTable('video_assignments');
        
        if ($tableExists) {
            // 기존 테이블이 있는 경우 (SQLite에서 마이그레이션)
            try {
                Schema::table('video_assignments', function (Blueprint $table) {
                    // 기존 UNIQUE 제약 조건 제거 시도
                    $table->dropUnique(['video_submission_id']);
                });
            } catch (\Exception $e) {
                // MySQL에서 외래키 제약조건 때문에 실패할 수 있음
                // 이 경우 수동으로 처리해야 함
            }
            
            // 새로운 복합 UNIQUE 제약 조건 추가
            Schema::table('video_assignments', function (Blueprint $table) {
                $table->unique(['video_submission_id', 'admin_id'], 'unique_video_admin_assignment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_assignments', function (Blueprint $table) {
            // 복합 UNIQUE 제약 조건 제거
            $table->dropUnique('unique_video_admin_assignment');
            
            // 기존 UNIQUE 제약 조건 복원
            $table->unique(['video_submission_id']);
        });
    }
};
