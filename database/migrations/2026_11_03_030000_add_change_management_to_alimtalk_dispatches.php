<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alimtalk_dispatches', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
            $table->json('pending_edit')->nullable();
            $table->boolean('delete_requested')->default(false);
            $table->text('change_error')->nullable();
            $table->timestamp('deleted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('alimtalk_dispatches', fn (Blueprint $table) => $table->dropColumn([
            'revision', 'pending_edit', 'delete_requested', 'change_error', 'deleted_at',
        ]));
    }
};
