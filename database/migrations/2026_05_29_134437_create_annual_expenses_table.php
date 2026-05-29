<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();

            // Template di origine. NULL per le spese "una tantum" create dentro
            // un anno (RB6). nullOnDelete: hard-delete del template non rompe le
            // istanze; il soft delete del template le lascia comunque intatte
            // (RB4: copia indipendente al momento dell'apertura).
            $table->foreignId('expense_item_id')->nullable()->constrained()->nullOnDelete();

            // Copia indipendente dei valori del template (RB5): aggiornare il
            // template NON altera le istanze già create.
            $table->string('name');
            // calculation_type: string + cast a [[App\Enums\ExpenseCalculationType]].
            // percentage_of_irpef_income | percentage_of_iva_revenue
            //                            | fixed_annual | sum_of_bolli.
            $table->string('calculation_type');
            $table->decimal('rate', 5, 2)->nullable();
            $table->decimal('minimum', 12, 2)->nullable();
            $table->decimal('maximum', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();

            // Override eccezionale: se non NULL prende il sopravvento sul
            // previsto calcolato (RB5: importo_definitivo = coalesce(effettivo, previsto)).
            $table->decimal('effective_amount', 12, 2)->nullable();

            // Solo per Imposta sostitutiva (RB5): credito IS dell'anno
            // precedente, precompilato dal sistema ma modificabile.
            $table->decimal('previous_year_credit', 12, 2)->nullable();

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_expenses');
    }
};
