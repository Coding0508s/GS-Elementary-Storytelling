<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alimtalk_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_token')->unique();
            $table->unsignedBigInteger('admin_id');
            $table->string('kind', 20);
            $table->string('scope', 20);
            $table->string('status', 20)->default('preview');
            $table->text('entry_link')->nullable();
            $table->string('configuration_hash', 64);
            $table->unsignedInteger('selected_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('previous_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });
        Schema::create('alimtalk_recipients', function (Blueprint $table) {
            $table->id();
            $table->uuid('dispatch_id');
            $table->string('phone', 16);
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->string('provider_group_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['dispatch_id', 'phone']);
            $table->index(['dispatch_id', 'status', 'id']);
        });
        Schema::create('alimtalk_dispatch_targets', function (Blueprint $table) {
            $table->id();
            $table->uuid('dispatch_id');
            $table->unsignedBigInteger('submission_id');
            $table->string('phone');
            $table->boolean('valid_phone');
            $table->string('identity_hash', 64);
            $table->unique(['dispatch_id', 'submission_id']);
            $table->index(['dispatch_id', 'phone']);
        });
        Schema::create('alimtalk_dispatch_locks', function (Blueprint $table) {
            $table->string('kind', 20)->primary();
            $table->uuid('dispatch_id')->nullable();
        });
        DB::table('alimtalk_dispatch_locks')->insert([
            ['kind' => 'morning'], ['kind' => 'entry'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('alimtalk_dispatch_locks');
        Schema::dropIfExists('alimtalk_dispatch_targets');
        Schema::dropIfExists('alimtalk_recipients');
        Schema::dropIfExists('alimtalk_dispatches');
    }
};
