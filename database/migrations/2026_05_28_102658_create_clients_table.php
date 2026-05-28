<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
