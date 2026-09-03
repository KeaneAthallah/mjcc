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
        Schema::create('crawl_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_run_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('http_status')->nullable();
            $table->text('message')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['crawl_source_id', 'occurred_at']);
            $table->index(['crawl_run_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_errors');
    }
};
