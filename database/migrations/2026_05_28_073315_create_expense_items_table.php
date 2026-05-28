<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Calculation type: store come string + cast a [[App\Enums\ExpenseCalculationType]]
            // sul Model. Si evita il CHECK constraint PostgreSQL dell'enum() Laravel
            // che richiede ALTER TABLE acrobatici per aggiungere valori. Validazione
            // dei valori ammessi gestita lato Form Request via Rule::enum().
            // Valori: percentage_of_irpef_income | percentage_of_iva_revenue
            //       | fixed_annual | sum_of_bolli.
            $table->string('calculation_type');
            $table->decimal('default_rate', 5, 2)->nullable();
            $table->decimal('default_minimum', 12, 2)->nullable();
            $table->decimal('default_maximum', 12, 2)->nullable();
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'active']);
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
