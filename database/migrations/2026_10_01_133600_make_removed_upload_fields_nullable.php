<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 업로드 폼에서 빠진 항목은 비워 둘 수 있게 하고,
     * 강사님께 궁금한 점을 저장할 칸을 추가합니다.
     */
    public function up(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->string('class_name')->nullable()->change();
            $table->string('student_name_english')->nullable()->change();
            $table->integer('age')->nullable()->change();
            $table->string('video_file_path')->nullable()->change();
            $table->string('video_file_name')->nullable()->change();
            $table->string('video_file_type')->nullable()->change();
            $table->bigInteger('video_file_size')->nullable()->change();
            $table->text('teacher_question')->nullable()->after('unit_topic');
        });
    }

    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn('teacher_question');
        });
    }
};
