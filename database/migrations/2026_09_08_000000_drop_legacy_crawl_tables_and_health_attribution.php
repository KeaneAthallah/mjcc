<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the legacy "Data Terintegrasi / Crawl" feature that was replaced by
 * the Data Publik module. Guarded so it is safe both on databases that had the
 * feature (existing dev/prod) and on fresh installs.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The legacy tables reference each other via foreign keys, so disable
        // constraint checks while dropping the set (drop-if-exists order is
        // irrelevant once FK checks are off).
        Schema::disableForeignKeyConstraints();

        try {
            foreach (['crawl_sources', 'crawl_runs', 'crawl_records', 'crawl_errors'] as $table) {
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // source attribution columns previously added to health_facilities
        // by the Kemenkes fasyankes crawler.
        $columns = ['source_name', 'source_url', 'source_id', 'source_updated_at', 'last_crawled_at'];

        if (Schema::hasTable('health_facilities')) {
            Schema::table('health_facilities', function ($table) use ($columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn('health_facilities', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * The legacy tables cannot be meaningfully restored, so the reverse simply
     * re-creates nothing but the health_facilities attribution columns schema.
     */
    public function down(): void
    {
        if (! Schema::hasTable('health_facilities')) {
            return;
        }

        Schema::table('health_facilities', function ($table) {
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_id')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
        });
    }
};
