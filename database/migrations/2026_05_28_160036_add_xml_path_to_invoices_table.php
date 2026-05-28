<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Path al file XML FatturaPA da cui la fattura è stata importata.
            // Nullable: le fatture create manualmente non hanno l'XML.
            // Storage: filesystem privato, mai esposto pubblicamente.
            $table->string('xml_path')->nullable()->after('art_15_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('xml_path');
        });
    }
};
