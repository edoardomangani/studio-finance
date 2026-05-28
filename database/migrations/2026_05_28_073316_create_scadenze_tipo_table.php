<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scadenze_tipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->unsignedTinyInteger('giorno');
            $table->unsignedTinyInteger('mese');
            $table->enum('tipo', ['pagamento', 'adempimento']);
            $table->foreignId('voce_spesa_id')->nullable()->constrained('voci_spesa')->nullOnDelete();
            $table->enum('anno_riferimento_spesa', ['corrente', 'successivo'])->default('corrente');
            $table->boolean('attiva')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'attiva']);
            $table->index(['user_id', 'mese', 'giorno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scadenze_tipo');
    }
};
