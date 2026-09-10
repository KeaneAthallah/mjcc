<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_prices', function (Blueprint $table) {
            $table->id();
            $table->string('commodity');
            $table->string('category')->nullable();
            $table->decimal('current_price', 18, 2)->nullable();
            $table->decimal('previous_price', 18, 2)->nullable();
            $table->decimal('price_change', 18, 2)->nullable();
            $table->decimal('percentage_change', 8, 2)->nullable();
            $table->string('market')->nullable();
            $table->string('region')->default('Kabupaten Morowali');
            $table->date('record_date');
            $table->string('availability')->nullable();
            $table->string('unit')->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index(['commodity', 'record_date']);
            $table->index(['category', 'record_date']);
            $table->index(['market', 'record_date']);
            $table->index('record_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_prices');
    }
};
