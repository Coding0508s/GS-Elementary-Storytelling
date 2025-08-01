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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique(); // 관리자 아이디
            $table->string('password'); // 비밀번호
            $table->string('name'); // 관리자 이름
            $table->string('email')->unique(); // 이메일
            $table->boolean('is_active')->default(true); // 활성 상태
            $table->timestamp('last_login_at')->nullable(); // 마지막 로그인 시간
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
