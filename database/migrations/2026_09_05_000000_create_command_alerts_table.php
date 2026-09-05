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
        Schema::create('command_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule');
            $table->string('severity');
            $table->string('sector_key');
            $table->string('sector');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('resource_slug')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->foreignId('kecamatan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('detail_route')->nullable();
            $table->json('detail_params')->nullable();
            $table->string('status')->default('baru')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index(['severity', 'status']);
            $table->index(['rule', 'resource_type', 'resource_id']);
            $table->index('kecamatan_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_alerts');
    }
};
