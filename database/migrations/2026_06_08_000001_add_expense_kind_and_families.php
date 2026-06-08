<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce il tipo fiscale `kind` sulle voci di spesa (template + annuali),
 * sostituendo il flag `is_pension_contribution`, e la tabella `expense_families`
 * (i quattro tipi come righe per-utente, rinominabili). Backfill: `kind` dai
 * flag esistenti, e 4 famiglie default per ogni utente già presente.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['expense_items', 'annual_expenses'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->string('kind')->nullable();
            });

            // pension per primo: cattura le 3 Inarcassa a prescindere dal
            // calculation_type (soggettivo %IRPEF, integrativo %IVA, maternità fissa).
            DB::table($table)->where('is_pension_contribution', true)->update(['kind' => 'pension']);
            DB::table($table)->whereNull('kind')->where('calculation_type', 'sum_of_bolli')->update(['kind' => 'stamp_duty']);
            DB::table($table)->whereNull('kind')->where('calculation_type', 'percentage_of_irpef_income')->update(['kind' => 'tax']);
            DB::table($table)->whereNull('kind')->update(['kind' => 'fixed']);

            Schema::table($table, function (Blueprint $t): void {
                $t->dropColumn('is_pension_contribution');
            });
        }

        Schema::create('expense_families', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind');
            $t->string('name');
            $t->timestamps();
            $t->unique(['user_id', 'kind']);
        });

        // Famiglie default per gli utenti esistenti (i nuovi le ricevono a onboarding).
        $defaults = ['tax' => 'Imposte', 'pension' => 'Previdenza', 'stamp_duty' => 'Bolli', 'fixed' => 'Costi fissi'];
        foreach (DB::table('users')->pluck('id') as $userId) {
            foreach ($defaults as $kind => $name) {
                DB::table('expense_families')->insert([
                    'user_id' => $userId,
                    'kind' => $kind,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_families');

        foreach (['expense_items', 'annual_expenses'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->boolean('is_pension_contribution')->default(false);
            });
            DB::table($table)->where('kind', 'pension')->update(['is_pension_contribution' => true]);
            Schema::table($table, function (Blueprint $t): void {
                $t->dropColumn('kind');
            });
        }
    }
};
