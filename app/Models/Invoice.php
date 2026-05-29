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
