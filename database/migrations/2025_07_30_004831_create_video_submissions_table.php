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
        Schema::create('video_submissions', function (Blueprint $table) {
            $table->id();
            
            // 학생 기본 정보
            $table->string('region'); // 거주 지역
            $table->string('institution_name'); // 기관명
            $table->string('class_name'); // 반 이름
            $table->string('student_name_korean'); // 학생 이름 (한글)
            $table->string('student_name_english'); // 학생 이름 (영어)
            $table->string('grade'); // 학년
            $table->integer('age'); // 나이
            
            // 학부모 정보
            $table->string('parent_name'); // 학부모 성함
            $table->string('parent_phone'); // 학부모 전화번호
            
            // 비디오 파일 정보
            $table->string('video_file_path'); // 비디오 파일 경로
            $table->string('video_file_name'); // 원본 파일명
            $table->string('video_file_type'); // 파일 형식 (MP4, MOV)
            $table->bigInteger('video_file_size'); // 파일 크기 (bytes)
            $table->string('unit_topic')->nullable(); // Unit 주제
            
            // 개인정보 및 상태 정보
            $table->boolean('privacy_consent')->default(false); // 개인정보 수집 동의
            $table->timestamp('privacy_consent_at')->nullable(); // 동의 시간
            $table->boolean('notification_sent')->default(false); // 알림 발송 여부
            $table->timestamp('notification_sent_at')->nullable(); // 알림 발송 시간
            $table->enum('status', ['uploaded', 'processing', 'completed', 'failed'])->default('uploaded'); // 처리 상태
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_submissions');
    }
};
