<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 오전 안내와 입장 링크의 발송 결과를 서로 덮어쓰지 않도록 종류별로 저장합니다.
     */
    public function up(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->json('alimtalk_results')->nullable()->after('alimtalk_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn('alimtalk_results');
        });
    }
};
