<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 설정 키 (예: 'contest_active')
            $table->text('value'); // 설정 값 (JSON 또는 문자열)
            $table->string('description')->nullable(); // 설정 설명
            $table->timestamps();
        });
        
        // 기본 설정 데이터 삽입
        DB::table('site_settings')->insert([
            [
                'key' => 'contest_active',
                'value' => 'true',
                'description' => '대회 페이지 활성화 상태',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
