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
        Schema::create('external_data', function (Blueprint $table) {
            $table->id();
            $table->string('sector', 20);
            $table->string('source', 50);
            $table->string('source_url');
            $table->string('dataset');
            $table->string('topic')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('location')->default('Kabupaten Morowali');
            $table->string('indicator');
            $table->decimal('value', 18, 4)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index(['sector', 'year']);
            $table->index(['sector', 'location', 'indicator']);
            $table->index('dataset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_data');
    }
};
