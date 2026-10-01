<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 광고성 정보 수신 동의 여부를 저장합니다.
     */
    public function up(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->boolean('marketing_consent')->default(false)->after('privacy_consent_at');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
        });
    }

    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn(['marketing_consent', 'marketing_consent_at']);
        });
    }
};
