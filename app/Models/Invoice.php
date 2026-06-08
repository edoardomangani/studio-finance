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
        'stamp_charged_to_client',
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
            'stamp_charged_to_client' => 'boolean',
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
     * Il bollo entra nel totale solo se a carico del cliente (rivalsa);
     * altrimenti lo studio lo assorbe e resta solo nei bolli da pagare.
     */
    protected function total(): Attribute
    {
        return Attribute::get(fn (): string => number_format(
            (float) $this->amount
            + (float) $this->inarcassa_amount
            + (($this->stamp_charged_to_client ?? true) ? (float) $this->stamp_amount : 0.0)
            + (float) $this->art_15_amount,
            2,
            '.',
            '',
        ));
    }

    /**
     * Ritenuta bancaria calcolata al volo se il flag è attivo, 0 altrimenti.
     * Derivata, mai persistita. Formula unica in [[InvoiceCalculator::withholding]]
     * (scorporo IVA + aliquota per data); mirror frontend in `useInvoiceTotals.ts`.
     */
    protected function withholdingAmount(): Attribute
    {
        return Attribute::get(fn (): string => number_format(
            $this->bank_withholding && $this->issued_at !== null
                ? InvoiceCalculator::withholding((float) $this->total, $this->issued_at)
                : 0.0,
            2,
            '.',
            '',
        ));
    }
}
