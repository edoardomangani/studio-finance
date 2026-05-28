<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: un cliente non può essere HARD-deletato se ha
            // fatture collegate (anche soft-deletate). Soft delete del cliente
            // resta ammesso, lasciando le fatture intatte (vedi RB2).
            $table->foreignId('client_id')->constrained()->restrictOnDelete();

            // Numero fattura: stringa, può contenere lettere/slash/dash
            // (es. "2026/001", "INV-2026-12", "A/1"). Univoco per
            // (user_id, anno_di_emissione) → vincolo enforced via Form Request
            // perché l'anno deriva da `issued_at` e PostgreSQL/SQLite
            // gestiscono in modo divergente expression unique index.
            $table->string('number', 64);
            $table->date('issued_at');

            // Importi: tutti decimal(12,2). Imponibile = base imponibile.
            // Cassa Inarcassa 4%: precompilata = imponibile × 4%, sovrascrivibile.
            // Bollo: default €2 se imponibile > €77,47, ma sempre editabile.
            // Art.15: spese anticipate fuori campo IVA, escluse da base
            // imponibile fiscale e contributiva.
            $table->decimal('amount', 12, 2);
            $table->decimal('inarcassa_amount', 12, 2)->default(0);
            $table->decimal('stamp_amount', 12, 2)->default(0);
            $table->decimal('art_15_amount', 12, 2)->default(0);

            // Ritenuta bancaria 8% sul totale fattura: flag precompilato dal
            // cliente, sovrascrivibile per fattura. L'importo della ritenuta
            // è derivato (totale × 8%), non stored.
            $table->boolean('bank_withholding')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indice principale: query annuali/mensili ordinate per data
            // (dashboard, vista anno, KPI cliente).
            $table->index(['user_id', 'issued_at']);
            // Indice per storico cliente: lookup `WHERE client_id = ?
            // ORDER BY issued_at DESC` su Show.vue.
            $table->index(['user_id', 'client_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
