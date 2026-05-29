<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Services\InvoiceCalculator;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'number',
        'issued_at',
        'amount',
        'inarcassa_amount',
        'stamp_amount',
        'art_15_amount',
        'bank_withholding',
        'xml_path',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'amount' => 'decimal:2',
            'inarcassa_amount' => 'decimal:2',
            'stamp_amount' => 'decimal:2',
            'art_15_amount' => 'decimal:2',
            'bank_withholding' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // `number_sort` è sempre derivato da `number` ad ogni salvataggio,
        // indipendentemente dal path di scrittura (InvoiceService o
        // ImportInvoices). Mai impostato a mano: single source of truth.
        static::saving(function (Invoice $invoice): void {
            $invoice->number_sort = self::naturalSortKey((string) $invoice->number);
        });
    }

    /**
     * Chiave di ordinamento "naturale" per `number`: ogni sequenza di cifre
     * viene zero-paddata a larghezza fissa, così l'ordinamento lessicografico
     * della chiave coincide con quello umano (`9` < `10` < `10A` < `10B`).
     * Identico su qualsiasi driver perché calcolato in PHP. Le parti non
     * numeriche sono lowercased per un ordine case-insensitive deterministico.
     */
    public static function naturalSortKey(string $number): string
    {
        $padded = preg_replace_callback(
            '/\d+/',
            fn (array $m): string => str_pad($m[0], 20, '0', STR_PAD_LEFT),
            $number,
        ) ?? $number;

        return strtolower($padded);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Totale fattura: imponibile + cassa + bollo + art.15. Derivato, mai
     * persistito (single source of truth = le quattro colonne).
     */
    protected function total(): Attribute
    {
        return Attribute::get(fn (): string => number_format(
            (float) $this->amount
            + (float) $this->inarcassa_amount
            + (float) $this->stamp_amount
            + (float) $this->art_15_amount,
            2,
            '.',
            '',
        ));
    }

    /**
     * Ritenuta bancaria 8% sul totale, calcolata al volo se il flag è attivo.
     * 0 se disattivata. Derivato, mai persistito.
     *
     * L'aliquota legale vive in [[InvoiceCalculator::BANK_WITHHOLDING_RATE]]
     * (single source of truth): evita drift se cambia. Sync col composable
     * frontend `useInvoiceTotals.ts` documentato in InvoiceCalculator.
     */
    protected function withholdingAmount(): Attribute
    {
        return Attribute::get(fn (): string => number_format(
            $this->bank_withholding
                ? (float) $this->total * InvoiceCalculator::BANK_WITHHOLDING_RATE
                : 0.0,
            2,
            '.',
            '',
        ));
    }
}
