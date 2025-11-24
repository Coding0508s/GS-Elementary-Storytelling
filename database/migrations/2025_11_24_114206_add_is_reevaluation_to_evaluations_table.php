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
            // is_reevaluation 필드 추가
            $table->boolean('is_reevaluation')->default(false)->after('award');
        });
        
        // SQLite에서 unique 인덱스를 직접 삭제
        if (Schema::hasTable('evaluations')) {
            try {
                \DB::statement('DROP INDEX IF EXISTS evaluations_video_submission_id_admin_id_unique');
            } catch (\Exception $e) {
                // 인덱스가 없거나 이미 삭제된 경우 무시
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // is_reevaluation 필드 삭제
            $table->dropColumn('is_reevaluation');
        });
        
        // 기존 unique 제약 조건 복원
        if (Schema::hasTable('evaluations')) {
            \DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS evaluations_video_submission_id_admin_id_unique ON evaluations (video_submission_id, admin_id)');
        }
    }
};
