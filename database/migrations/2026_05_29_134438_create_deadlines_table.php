<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();

            // Scadenza tipo di origine. NULL per le scadenze manuali. nullOnDelete:
            // hard-delete del template non rompe le istanze già generate.
            $table->foreignId('recurring_deadline_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->date('due_at');
            // kind: string + cast a [[App\Enums\DeadlineKind]]. payment | fulfillment.
            $table->string('kind');

            // Solo per kind=payment (RB8): la spesa annuale pagata. Per le
            // scadenze cross-year (Commercialista) punta alla spesa di N+1.
            // nullOnDelete coerente con le altre FK verso entità soft-deletabili.
            $table->foreignId('annual_expense_id')->nullable()->constrained()->nullOnDelete();

            // status: string + cast a [[App\Enums\DeadlineStatus]].
            // open | completed | not_due. Reversibili tutti con conferma (F9).
            $table->string('status')->default('open');

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'due_at']);
            // Lookup scadenze di una spesa (Fase 7/9). Postgres non indicizza
            // automaticamente le FK.
            $table->index(['user_id', 'annual_expense_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
