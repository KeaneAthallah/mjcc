<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag which pipeline produced a master row so the real-data sync can target
     * only the rows it owns. Existing seeded placeholder rows are frozen as
     * 'seed'; rows created later (via CRUD) keep null and are never retired.
     */
    public function up(): void
    {
        foreach ($this->tables() as $table => $after) {
            Schema::table($table, function (Blueprint $blueprint) use ($after) {
                $blueprint->string('source')->nullable()->after($after)->index();
            });
        }

        foreach (array_keys($this->tables()) as $table) {
            DB::table($table)->whereNull('source')->update(['source' => 'seed']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_keys($this->tables()) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('source');
            });
        }
    }

    /**
     * @return array<string, string> column after which the source column lands
     */
    private function tables(): array
    {
        return [
            'health_facilities' => 'status',
            'polseks' => 'status',
            'poskamlings' => 'is_active',
            'tipkamtikmas' => 'status',
            'markets' => 'status',
        ];
    }
};
