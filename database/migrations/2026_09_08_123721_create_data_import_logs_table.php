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
        Schema::create('data_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sector');
            $table->string('status')->default('berjalan');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('datasets_scanned')->default(0);
            $table->unsignedInteger('entity_datasets')->default(0);
            $table->unsignedInteger('entities_created')->default(0);
            $table->unsignedInteger('entities_updated')->default(0);
            $table->unsignedInteger('entities_skipped')->default(0);
            $table->unsignedInteger('entities_failed')->default(0);
            $table->unsignedInteger('kecamatan_created')->default(0);
            $table->unsignedInteger('kecamatan_updated')->default(0);
            $table->unsignedInteger('locations_resolved')->default(0);
            $table->text('error_summary')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['sector', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_import_logs');
    }
};
