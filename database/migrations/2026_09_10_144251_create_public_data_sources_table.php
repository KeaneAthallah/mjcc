<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->index();
            $table->text('description')->nullable();
            $table->string('source_url')->nullable();
            $table->string('adapter_class');
            $table->boolean('enabled')->default(true);
            $table->string('refresh_frequency', 50)->default('daily');
            $table->boolean('supports_map')->default(false);
            $table->boolean('supports_chart')->default(true);
            $table->boolean('supports_table')->default(true);
            $table->string('status', 20)->default('belum');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->unsignedInteger('sync_duration_ms')->nullable();
            $table->string('data_period')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['category', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_data_sources');
    }
};
