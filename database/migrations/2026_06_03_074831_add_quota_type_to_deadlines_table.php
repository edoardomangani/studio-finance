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
        Schema::table('deadlines', function (Blueprint $table) {
            // Copiato dalla scadenza tipo all'apertura anno; resta editabile
            // sull'istanza (RB8). Nullable, solo per kind=payment.
            $table->string('quota_type')->nullable()->after('annual_expense_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deadlines', function (Blueprint $table) {
            $table->dropColumn('quota_type');
        });
    }
};
