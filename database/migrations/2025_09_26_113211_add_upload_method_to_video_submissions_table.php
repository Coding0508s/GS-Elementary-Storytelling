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
            $table->string('video_url')->nullable()->after('video_file_size');
            $table->string('upload_method')->default('server')->after('video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn(['upload_method', 'video_url']);
        });
    }
};
