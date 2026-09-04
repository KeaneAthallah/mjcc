<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_facilities', function (Blueprint $table) {
            $table->string('source_name')->nullable()->after('description');
            $table->string('source_url')->nullable()->after('source_name');
            $table->string('source_id')->nullable()->after('source_url');
            $table->timestamp('source_updated_at')->nullable()->after('source_id');
            $table->timestamp('last_crawled_at')->nullable()->after('source_updated_at');

            $table->index('source_name');
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('health_facilities', function (Blueprint $table) {
            $table->dropIndex(['source_name']);
            $table->dropIndex(['source_id']);
            $table->dropColumn([
                'source_name', 'source_url', 'source_id',
                'source_updated_at', 'last_crawled_at',
            ]);
        });
    }
};
