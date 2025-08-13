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
            $table->string('qualification_status')->default('pending')->after('comments');
            $table->integer('rank_by_judge')->nullable()->after('qualification_status');
            $table->timestamp('qualified_at')->nullable()->after('rank_by_judge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['qualification_status', 'rank_by_judge', 'qualified_at']);
        });
    }
};