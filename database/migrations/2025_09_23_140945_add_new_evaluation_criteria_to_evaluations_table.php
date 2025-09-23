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
        Schema::table('evaluations', function (Blueprint $table) {
            // 새로운 평가 기준 추가 (각 10점 만점)
            $table->integer('topic_connection_score')->nullable()->after('confidence_score'); // 주제와 발표 내용과의 연결성
            $table->integer('structure_flow_score')->nullable()->after('topic_connection_score'); // 자연스러운 구성과 흐름
            $table->integer('creativity_score')->nullable()->after('structure_flow_score'); // 창의적 내용
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // 추가한 컬럼들 삭제
            $table->dropColumn(['topic_connection_score', 'structure_flow_score', 'creativity_score']);
        });
    }
};
