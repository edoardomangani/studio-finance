<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // P.IVA italiana (Partita IVA): 11 cifre. CF (Codice Fiscale):
            // 16 char per persone fisiche, 11 per enti. Tenuti stringa per
            // non perdere zeri iniziali; almeno uno dei due deve essere
            // valorizzato (vincolo enforced a livello Form Request).
            //
            // GDPR: tax_code è PII sensibile (contiene data nascita, sesso,
            // luogo). Plaintext accettato in Fase 3 dev; encryption at rest
            // tracciato in CLAUDE.md "Pre-production hardening" prima del
            // go-live con utenti reali.
            $table->string('vat_number', 32)->nullable();
            $table->string('tax_code', 32)->nullable();
            // Ritenuta bancaria 8%: flag default sul cliente che propaga
            // al campo `apply_bank_withholding` di ogni nuova fattura
            // (sovrascrivibile per fattura).
            $table->boolean('bank_withholding')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'vat_number']);
            $table->index(['user_id', 'tax_code']);
        });

        // Partial unique index su (user_id, vat_number) e (user_id, tax_code)
        // quando valorizzati: un utente non può avere due clienti con la
        // stessa P.IVA/CF (anti-duplicato all'import XML in Fase 5).
        // Più clienti con vat_number/tax_code NULL sono ammessi.
        // Sintassi PostgreSQL/SQLite (entrambi supportano partial index).
        DB::statement(
            'CREATE UNIQUE INDEX clients_user_vat_unique '
            .'ON clients (user_id, vat_number) WHERE vat_number IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX clients_user_tax_unique '
            .'ON clients (user_id, tax_code) WHERE tax_code IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
