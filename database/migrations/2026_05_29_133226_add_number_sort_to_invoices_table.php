<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chiave di ordinamento naturale di `number` (cifre zero-paddate):
        // l'ordinamento lessicografico di questa colonna == ordine umano
        // (`9` < `10` < `10A`). Popolata da [[Invoice]] ad ogni saving.
        // Larghezza generosa: un run a 20 cifre + suffissi/separatori.
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('number_sort', 128)->default('')->after('number');
            $table->index(['user_id', 'issued_at', 'number_sort']);
        });

        // Backfill record esistenti. Query builder diretto: il model Invoice
        // ha il global scope BelongsToUser su Auth::id(), assente in migration.
        DB::table('invoices')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('invoices')
                    ->where('id', $row->id)
                    ->update(['number_sort' => Invoice::naturalSortKey((string) $row->number)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'issued_at', 'number_sort']);
            $table->dropColumn('number_sort');
        });
    }
};
