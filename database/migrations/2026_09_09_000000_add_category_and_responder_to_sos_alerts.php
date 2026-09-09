<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->string('category', 20)->default('general')->index()->after('status');
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete()->after('resolved_at');
            $table->timestamp('accepted_at')->nullable()->after('accepted_by');
        });
    }

    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['category', 'accepted_by', 'accepted_at']);
        });
    }
};
