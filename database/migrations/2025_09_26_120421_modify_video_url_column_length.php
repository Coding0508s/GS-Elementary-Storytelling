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
        Schema::table('video_submissions', function (Blueprint $table) {
            // video_url 컬럼 길이를 500자로 확장 (nullable 허용)
            $table->string('video_url', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            // video_url 컬럼을 원래 길이로 되돌리기
            $table->string('video_url', 255)->nullable()->change();
        });
    }
};
