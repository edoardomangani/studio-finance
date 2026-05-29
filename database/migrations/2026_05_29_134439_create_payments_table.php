<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Sempre collegato a una sola spesa annuale (RB9). cascadeOnDelete
            // a livello DB: l'hard-delete di una spesa porta via i pagamenti.
            // Il blocco applicativo (RB14: spesa con pagamenti pagati non
            // cancellabile) vive nel service, non a schema.
            $table->foreignId('annual_expense_id')->constrained()->cascadeOnDelete();

            // NULL per i pagamenti manuali extra-scadenza (F8). nullOnDelete:
            // se la scadenza sparisce il pagamento resta come manuale.
            $table->foreignId('deadline_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description')->nullable();
            // Vuoti finché lo stato è pianificato (RB9): valorizzati alla
            // registrazione del pagamento (F7).
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('paid_at')->nullable();

            // status: string + cast a [[App\Enums\PaymentStatus]].
            // planned | paid | not_due. Solo `paid` concorre ai totali (RB9).
            $table->string('status')->default('planned');

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Query "contributi pagati nell'anno" per data di cassa (RB9).
            $table->index(['user_id', 'paid_at']);
            // Totali per spesa (importoPagato, Fase 9): Postgres non indicizza
            // automaticamente le FK.
            $table->index(['user_id', 'annual_expense_id']);
            $table->index('deadline_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
