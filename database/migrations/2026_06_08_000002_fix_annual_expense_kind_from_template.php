<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corregge il `kind` delle spese annuali ereditandolo dal template collegato
 * (`expense_item_id`), che è la fonte autoritativa. Il backfill precedente si
 * basava su `is_pension_contribution`, ma le istanze annuali aperte prima che
 * quel flag fosse propagato lo avevano a false → Inarcassa finiva in tax/fixed
 * invece di pension. Le una-tantum (senza template) restano com'erano.
 * Idempotente: ri-eseguibile senza effetti collaterali.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'UPDATE annual_expenses
             SET kind = (SELECT ei.kind FROM expense_items ei WHERE ei.id = annual_expenses.expense_item_id)
             WHERE expense_item_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        // Correzione dati: nessun rollback sensato.
    }
};
