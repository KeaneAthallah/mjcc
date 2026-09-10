<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_risk_indices', function (Blueprint $table) {
            $table->id();
            $table->string('region_name');
            $table->string('region_code', 20)->nullable();
            $table->string('hazard_type')->nullable();
            $table->decimal('risk_index', 10, 4)->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->decimal('vulnerability_index', 10, 4)->nullable();
            $table->decimal('exposure_index', 10, 4)->nullable();
            $table->decimal('capacity_index', 10, 4)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index(['hazard_type', 'risk_level']);
            $table->index(['region_code', 'year']);
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_risk_indices');
    }
};
