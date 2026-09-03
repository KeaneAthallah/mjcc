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
        Schema::create('crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_source_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('running'); // running | success | partial | failed
            $table->unsignedBigInteger('records_found')->default(0);
            $table->unsignedBigInteger('records_created')->default(0);
            $table->unsignedBigInteger('records_updated')->default(0);
            $table->unsignedBigInteger('records_unchanged')->default(0);
            $table->unsignedBigInteger('records_failed')->default(0);
            $table->unsignedBigInteger('error_count')->default(0);
            $table->bigInteger('duration')->nullable();
            $table->json('log')->nullable();
            $table->timestamps();

            $table->index(['crawl_source_id', 'started_at']);
            $table->index(['crawl_source_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_runs');
    }
};
