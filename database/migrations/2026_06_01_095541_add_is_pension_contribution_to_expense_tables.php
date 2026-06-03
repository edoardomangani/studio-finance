<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca le spese che sono contributi previdenziali (Inarcassa): identifica i
     * contributi pagati nell'anno e fa usare il reddito IRPEF lordo invece del netto.
     */
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->boolean('is_pension_contribution')->default(false);
        });

        Schema::table('annual_expenses', function (Blueprint $table) {
            $table->boolean('is_pension_contribution')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn('is_pension_contribution');
        });

        Schema::table('annual_expenses', function (Blueprint $table) {
            $table->dropColumn('is_pension_contribution');
        });
    }
};
