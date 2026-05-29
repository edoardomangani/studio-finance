<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Anno solare (es. 2026). Aperto manualmente dall'utente. Nessuno
            // stato esplicito "aperto/chiuso" (RB10): l'esistenza del record =
            // anno aperto.
            $table->integer('year');

            // Copia indipendente del coefficiente di redditività dal profilo
            // al momento dell'apertura (RB10). Modificabile poi per anno senza
            // toccare il profilo; la propagazione esplicita arriva in Fase 10.
            $table->decimal('profitability_coefficient', 5, 2);

            // Anno "pre-aperto": creato implicitamente per coprire una scadenza
            // cross-year (Commercialista 31/12/N paga la spesa di N+1, RB8/RB10).
            // Contiene solo le istanze strettamente necessarie. Quando l'utente
            // lo apre formalmente, il wizard lo rileva e completa le istanze
            // mancanti riusando quelle esistenti, invece di bloccare con
            // "anno già aperto".
            $table->boolean('pre_opened')->default(false);

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Un solo anno N per utente. Partial index su deleted_at IS NULL così
        // un'eventuale archiviazione futura non impedisce la riapertura.
        // Sintassi PostgreSQL/SQLite (entrambi supportano partial index).
        DB::statement(
            'CREATE UNIQUE INDEX years_user_year_unique '
            .'ON years (user_id, year) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('years');
    }
};
