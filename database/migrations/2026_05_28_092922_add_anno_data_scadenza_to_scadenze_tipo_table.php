<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scadenze_tipo', function (Blueprint $table) {
            // Anno calendariale della data scadenza, relativo all'anno del
            // wizard. Default 'corrente' = la data cade nello stesso anno.
            // 'successivo' = la data cade in N+1 (es. saldo IS 30/06/N+1).
            // Ortogonale ad anno_riferimento_spesa (che dice QUALE spesa
            // viene pagata, non quando).
            $table->enum('anno_data_scadenza', ['corrente', 'successivo'])
                ->default('corrente')
                ->after('voce_spesa_id');
        });
    }

    public function down(): void
    {
        Schema::table('scadenze_tipo', function (Blueprint $table) {
            $table->dropColumn('anno_data_scadenza');
        });
    }
};
