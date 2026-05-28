<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            // kind: payment (collegato a expense item) o fulfillment (informativo,
            // es. dichiarazione redditi, Dich.RED Inarcassa).
            $table->enum('kind', ['payment', 'fulfillment']);
            $table->foreignId('expense_item_id')->nullable()->constrained()->nullOnDelete();
            // due_year_offset: la data scadenza cade in anno N (current) o N+1 (next).
            // Es. saldo IS 30/06/N+1, bolli Q4 28/02/N+1, mentre acconti 30/06/N.
            $table->enum('due_year_offset', ['current', 'next'])->default('current');
            // expense_year_offset: la scadenza paga la spesa di N (current) o N+1 (next).
            // Es. commercialista 31/12/N paga parcella anno N+1.
            $table->enum('expense_year_offset', ['current', 'next'])->default('current');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'active']);
            $table->index(['user_id', 'month', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_deadlines');
    }
};
