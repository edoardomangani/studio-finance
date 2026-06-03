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
        Schema::table('recurring_deadlines', function (Blueprint $table) {
            // quota_type: string + cast a [[App\Enums\QuotaType]]. Nullable,
            // solo per kind=payment. Determina come si calcola l'importo
            // previsto della scadenza generata (RB8). Stesso razionale degli
            // altri enum: string + Rule::enum() invece di CHECK Postgres.
            $table->string('quota_type')->nullable()->after('expense_year_offset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_deadlines', function (Blueprint $table) {
            $table->dropColumn('quota_type');
        });
    }
};
