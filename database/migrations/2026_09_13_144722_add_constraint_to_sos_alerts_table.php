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
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->string('constraint_type', 20)->nullable()->after('accepted_at');
            $table->string('constraint_reason', 500)->nullable()->after('constraint_type');
            $table->foreignId('constrained_by')->nullable()
                ->after('constraint_reason')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('constrained_at')->nullable()->after('constrained_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('constrained_by');
            $table->dropColumn(['constraint_type', 'constraint_reason', 'constrained_at']);
        });
    }
};
