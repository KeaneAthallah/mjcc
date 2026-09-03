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
        Schema::create('crawl_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('record_type')->default('record');
            $table->string('name')->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('kabupaten_code', 10)->nullable();
            $table->string('kabupaten_name')->nullable();
            $table->string('kecamatan_code', 20)->nullable();
            $table->string('kecamatan_name')->nullable();
            $table->string('desa_code', 20)->nullable();
            $table->string('desa_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('data')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['crawl_source_id', 'external_id']);
            $table->index('record_type');
            $table->index('kabupaten_code');
            $table->index('kecamatan_code');
            $table->index('desa_code');
            $table->index('last_seen_at');
            $table->index(['latitude', 'longitude']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_records');
    }
};
