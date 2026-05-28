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
            // kind / due_year_offset / expense_year_offset: salvati come string +
            // cast a enum lato Model. Razionale: niente CHECK constraint Postgres
            // da rinegoziare per aggiungere valori. Validazione via Rule::enum()
            // nei Form Request.
            //
            // kind:                payment | fulfillment
            // due_year_offset:     current | next   (anno calendariale della data)
            // expense_year_offset: current | next   (anno della spesa target)
            $table->string('kind');
            $table->foreignId('expense_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('due_year_offset')->default('current');
            $table->string('expense_year_offset')->default('current');
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
