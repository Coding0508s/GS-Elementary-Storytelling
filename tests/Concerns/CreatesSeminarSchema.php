<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesSeminarSchema
{
    protected function createSeminarSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('judge');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('video_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('institution_name');
            $table->string('class_name')->nullable();
            $table->string('student_name_korean');
            $table->string('student_name_english')->nullable();
            $table->string('grade');
            $table->integer('age')->nullable();
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('video_file_path')->nullable();
            $table->string('video_file_name')->nullable();
            $table->string('video_file_type')->nullable();
            $table->unsignedBigInteger('video_file_size')->nullable();
            $table->string('unit_topic')->nullable();
            $table->text('teacher_question')->nullable();
            $table->boolean('privacy_consent')->default(false);
            $table->timestamp('privacy_consent_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('status')->default('uploaded');
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['evaluations', 'video_assignments', 'ai_evaluations'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('video_submission_id')->nullable();
                $table->timestamps();
            });
        }
    }
}
