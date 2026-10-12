<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 관리자가 보낸 알림의 마지막 결과를 접수 행에 남깁니다.
     */
    public function up(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->string('alimtalk_status', 20)->nullable()->after('notification_sent_at');
            $table->string('alimtalk_status_reason')->nullable()->after('alimtalk_status');
            $table->timestamp('alimtalk_sent_at')->nullable()->after('alimtalk_status_reason');
        });
    }

    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn(['alimtalk_status', 'alimtalk_status_reason', 'alimtalk_sent_at']);
        });
    }
};
