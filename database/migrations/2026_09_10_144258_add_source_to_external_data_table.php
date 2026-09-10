<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_data', function (Blueprint $table) {
            $table->string('source_key', 50)->default('satudata')->after('source');
            $table->index('source_key');
        });
    }

    public function down(): void
    {
        Schema::table('external_data', function (Blueprint $table) {
            $table->dropIndex(['source_key']);
            $table->dropColumn('source_key');
        });
    }
};
