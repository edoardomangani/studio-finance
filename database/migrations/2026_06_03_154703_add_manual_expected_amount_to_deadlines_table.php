<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deadlines', function (Blueprint $table) {
            // Previsto manuale per le scadenze ad-hoc (RB8): le standard derivano
            // il previsto da quota_type + spesa, le ad-hoc non hanno quota_type
            // (eviterebbe il coupling sullo split delle fratelle), quindi qui
            // l'utente fissa il suggerimento. Nullable; letto solo dal ramo
            // ad-hoc di [[App\Services\DeadlineExpectation]].
            $table->decimal('manual_expected_amount', 12, 2)->nullable()->after('quota_type');
        });
    }

    public function down(): void
    {
        Schema::table('deadlines', function (Blueprint $table) {
            $table->dropColumn('manual_expected_amount');
        });
    }
};
