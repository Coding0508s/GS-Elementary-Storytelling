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
            // 기존 점수 필드들의 범위가 0~10으로 변경됨을 명확히 하기 위한 주석 업데이트
            $table->integer('pronunciation_score')->comment('정확한 발음과 자연스러운 억양, 전달력 (0-10점)')->change();
            $table->integer('vocabulary_score')->comment('올바른 어휘 및 표현 사용 (0-10점)')->change();
            $table->integer('fluency_score')->comment('유창성 수준 (0-10점)')->change();
            $table->integer('confidence_score')->comment('자신감, 긍정적이고 밝은 태도 (0-10점)')->change();
            
            // 환산 점수 필드들 추가 (소수점 1자리)
            $table->decimal('pronunciation_converted', 4, 1)->default(0)->comment('발음 환산 점수 (0.0-100.0)');
            $table->decimal('vocabulary_converted', 4, 1)->default(0)->comment('어휘 환산 점수 (0.0-100.0)');
            $table->decimal('fluency_converted', 4, 1)->default(0)->comment('유창성 환산 점수 (0.0-100.0)');
            $table->decimal('confidence_converted', 4, 1)->default(0)->comment('자신감 환산 점수 (0.0-100.0)');
            
            // 총점은 여전히 4개 점수의 합계 (0-40점)
            $table->integer('total_score')->comment('총 점수 합계 (0-40점)')->change();
            
            // 환산 후 총점 (항상 100.0)
            $table->decimal('total_converted', 5, 1)->default(100.0)->comment('환산 총점 (100.0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // 기존 주석으로 되돌리기
            $table->integer('pronunciation_score')->comment('정확한 발음과 자연스러운 억양, 전달력 (1-10점)')->change();
            $table->integer('vocabulary_score')->comment('올바른 어휘 및 표현 사용 (1-10점)')->change();
            $table->integer('fluency_score')->comment('유창성 수준 (1-10점)')->change();
            $table->integer('confidence_score')->comment('자신감, 긍정적이고 밝은 태도 (1-10점)')->change();
            $table->integer('total_score')->comment('총점 (자동 계산)')->change();
            
            // 환산 점수 필드들 삭제
            $table->dropColumn([
                'pronunciation_converted',
                'vocabulary_converted', 
                'fluency_converted',
                'confidence_converted',
                'total_converted'
            ]);
        });
    }
};
