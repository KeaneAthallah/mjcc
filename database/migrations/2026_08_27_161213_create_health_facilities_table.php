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
        Schema::create('health_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('facility_type');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('condition')->default('baik');
            $table->unsignedInteger('beds')->default(0);
            $table->unsignedInteger('doctors')->default(0);
            $table->unsignedInteger('nurses')->default(0);
            $table->unsignedInteger('midwives')->default(0);
            $table->string('status')->default('aktif');
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kecamatan_id', 'facility_type']);
            $table->index('facility_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_facilities');
    }
};
