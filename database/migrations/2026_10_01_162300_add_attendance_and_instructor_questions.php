<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 참석 일자와 강사별 질문을 따로 저장합니다.
     */
    public function up(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->string('attendance_day')->nullable()->after('teacher_question');
            $table->text('question_day1')->nullable()->after('attendance_day');
            $table->text('question_day2')->nullable()->after('question_day1');
        });
    }

    public function down(): void
    {
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->dropColumn(['attendance_day', 'question_day1', 'question_day2']);
        });
    }
};
