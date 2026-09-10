<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bps_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('dataset_id', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject')->nullable();
            $table->string('period_type', 50)->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bps_datasets');
    }
};
