<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            // Il nome del professionista è users.name (single source of truth):
            // qui solo i dati specifici di dominio fiscale.
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // profitability_coefficient: per architetti Inarcassa = 78%.
            $table->decimal('profitability_coefficient', 5, 2);
            // business_start_year: determina se l'aliquota IS è 5% (primi 5 anni) o 15%.
            $table->year('business_start_year');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};
