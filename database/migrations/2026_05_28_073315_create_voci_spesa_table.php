<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voci_spesa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo_calcolo', [
                'perc_reddito_irpef',
                'perc_volume_affari_iva',
                'fissa_annuale',
                'somma_bolli',
            ]);
            $table->decimal('aliquota_default', 5, 2)->nullable();
            $table->decimal('minimale_default', 12, 2)->nullable();
            $table->decimal('massimale_default', 12, 2)->nullable();
            $table->decimal('quota_default', 12, 2)->nullable();
            $table->boolean('attiva')->default(true);
            $table->unsignedInteger('ordine')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'attiva']);
            $table->index(['user_id', 'ordine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voci_spesa');
    }
};
