<?php

use App\Models\Invoice;
use App\Services\RevenueCalculator;
use Illuminate\Support\Collection;

/**
 * Una nota di credito è modellata come fattura a importo negativo
 * (vedi InvoiceValidationRules). Le aggregazioni devono stornarla
 * linearmente: questo test blocca il comportamento "viene scalato".
 */
function invoice(float $amount, float $inarcassa = 0, float $stamp = 0, float $art15 = 0, bool $charged = true): Invoice
{
    return new Invoice([
        'amount' => $amount,
        'inarcassa_amount' => $inarcassa,
        'stamp_amount' => $stamp,
        'stamp_charged_to_client' => $charged,
        'art_15_amount' => $art15,
    ]);
}

it('storna l\'imponibile di una nota di credito dal fatturato', function (): void {
    $calc = new RevenueCalculator;

    $invoices = new Collection([
        invoice(1000.00, 40.00, 2.00),  // fattura
        invoice(-300.00, -12.00, 2.00), // nota di credito
    ]);

    // 1000 + (-300) = 700.
    expect($calc->taxableAmount($invoices))->toBe(700.0);
});

it('storna il totale di una nota di credito', function (): void {
    $calc = new RevenueCalculator;

    $invoices = new Collection([
        invoice(1000.00, 40.00, 2.00),  // total 1042
        invoice(-300.00, -12.00, 2.00), // total -310
    ]);

    // 1042 + (-310) = 732.
    expect($calc->invoiceTotal($invoices))->toBe(732.0);
});

it('bollo non a carico del cliente: fuori dal totale ma resta nei bolli da pagare', function (): void {
    $calc = new RevenueCalculator;

    $invoices = new Collection([
        invoice(1000.00, 40.00, 2.00, 0.00, charged: false),
    ]);

    // Il bollo è sempre dovuto → conteggiato nei bolli da pagare.
    expect($calc->stampDuty($invoices))->toBe(2.0)
        // ...ma escluso dal totale: 1000 + 40 (no bollo) = 1040.
        ->and($calc->invoiceTotal($invoices))->toBe(1040.0)
        // Volume d'affari IVA = imponibile + bolli: include sempre il bollo
        // (scelta fiscale), 1000 + 2.
        ->and($calc->vatTurnover($invoices))->toBe(1002.0);
});
