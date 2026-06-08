<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Rivalsa bollo: true = bollo addebitato al cliente (entra nel
            // totale); false = a carico dello studio (fuori dal totale). Il
            // bollo resta sempre in stamp_amount e nei bolli da pagare.
            // Default true = comportamento storico (bollo sempre nel totale).
            $table->boolean('stamp_charged_to_client')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('stamp_charged_to_client');
        });
    }
};
