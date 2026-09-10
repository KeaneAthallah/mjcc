<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique()->nullable();
            $table->string('disaster_type');
            $table->string('disaster_name')->nullable();
            $table->date('event_date')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('sub_district')->nullable();
            $table->string('village')->nullable();
            $table->text('affected_area')->nullable();
            $table->text('impact')->nullable();
            $table->unsignedInteger('affected_population')->default(0);
            $table->text('infrastructure_impact')->nullable();
            $table->string('status')->default('terkini');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('dedupe_key', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index(['disaster_type', 'event_date']);
            $table->index(['district', 'event_date']);
            $table->index('event_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_events');
    }
};
