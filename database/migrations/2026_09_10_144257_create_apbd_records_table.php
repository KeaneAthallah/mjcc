<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apbd_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('indicator');
            $table->string('category')->nullable();
            $table->decimal('target_value', 18, 2)->nullable();
            $table->decimal('realization_value', 18, 2)->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('previous_value', 18, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('region')->default('Kabupaten Morowali');
            $table->string('source_indicator_code')->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'indicator']);
            $table->index(['category', 'year']);
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apbd_records');
    }
};
