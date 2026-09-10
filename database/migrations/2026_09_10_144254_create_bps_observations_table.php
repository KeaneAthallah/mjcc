<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bps_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bps_dataset_id')->constrained()->cascadeOnDelete();
            $table->string('indicator');
            $table->string('region_name')->nullable();
            $table->string('region_code', 20)->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('period', 20)->nullable();
            $table->decimal('value', 18, 4)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['bps_dataset_id', 'year']);
            $table->index(['indicator', 'region_name']);
            $table->index(['region_code', 'year']);
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bps_observations');
    }
};
