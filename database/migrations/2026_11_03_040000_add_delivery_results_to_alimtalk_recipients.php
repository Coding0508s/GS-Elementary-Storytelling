<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alimtalk_recipients', function (Blueprint $table) {
            $table->timestamp('result_checked_at')->nullable();
            $table->text('result_error')->nullable();
            $table->string('provider_status_code', 10)->nullable();
            $table->string('delivery_channel', 30)->nullable();
            $table->index(['status', 'result_checked_at'], 'alimtalk_results_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('alimtalk_recipients', function (Blueprint $table) {
            $table->dropIndex('alimtalk_results_due_index');
            $table->dropColumn(['result_checked_at', 'result_error', 'provider_status_code', 'delivery_channel']);
        });
    }
};
