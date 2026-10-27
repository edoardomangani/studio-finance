<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            // Giorni mancanti al momento dell'invio (negativo = scaduta). Solo informativo.
            $table->integer('days_until_due');
            // Data Europe/Rome dell'invio: chiave di idempotenza con deadline_id.
            $table->date('sent_on');
            $table->timestamps();

            $table->unique(['deadline_id', 'sent_on']);
            // Postgres non indicizza le FK: serve al cascade sulla cancellazione utente.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_notifications');
    }
};
