<?php

use App\Services\InvoiceCalculator;

/**
 * Parity test: itera la fixture JSON condivisa con
 * `resources/js/composables/useInvoiceTotals.ts` (sync manuale, vedi
 * commento in testa al composable). Se le due formule divergono, questo
 * test diventa rosso prima che la divergenza arrivi in production.
 */
function calcCases(): array
{
    $path = __DIR__.'/../Fixtures/invoice_calc_cases.json';
    $json = file_get_contents($path) ?: '[]';

    return json_decode($json, true) ?? [];
}

it('canonicalizza ogni caso della fixture come atteso', function (): void {
    $calc = new InvoiceCalculator;
    $cases = calcCases();

    expect($cases)->not->toBeEmpty();

    foreach ($cases as $case) {
        $actual = $calc->canonicalize($case['input']);

        foreach ($case['expected'] as $field => $expectedValue) {
            expect($actual[$field])
                ->toEqualWithDelta($expectedValue, 0.001, "Caso '{$case['name']}', campo '{$field}'");
        }
    }
});

it('defaultStamp: €2 sopra €77,47, 0 altrimenti, esclusivo sulla soglia', function (): void {
    $calc = new InvoiceCalculator;

    expect($calc->defaultStamp(0.0))->toBe(0.0)
        ->and($calc->defaultStamp(77.47))->toBe(0.0)
        ->and($calc->defaultStamp(77.48))->toBe(2.00)
        ->and($calc->defaultStamp(1000.0))->toBe(2.00);
});

it('defaultInarcassa: (imponibile + bollo) × 4%', function (): void {
    $calc = new InvoiceCalculator;

    expect($calc->defaultInarcassa(1000.0, 2.0))->toBe(40.08)
        ->and($calc->defaultInarcassa(1000.0, 0.0))->toBe(40.00)
        ->and($calc->defaultInarcassa(0.0, 0.0))->toBe(0.0);
});

it('totals: somma + ritenuta scorporata per data se flag attivo', function (): void {
    $calc = new InvoiceCalculator;
    $date = new DateTimeImmutable('2026-01-15');

    $without = $calc->totals(1000.0, 2.0, 40.08, 0.0, false, $date);
    expect($without['total'])->toBe(1042.08)
        ->and($without['withholding_amount'])->toBe(0.0)
        ->and($without['net_amount'])->toBe(1042.08);

    $with = $calc->totals(1000.0, 2.0, 40.08, 0.0, true, $date);
    expect($with['total'])->toBe(1042.08)
        ->and($with['withholding_amount'])->toBe(93.96)   // 1042.08 / 1.22 × 11%
        ->and($with['net_amount'])->toBe(948.12);
});

it('withholding: scorporo IVA 22% e aliquota 8% (≤29/2/2024) / 11% (≥1/3/2024)', function (): void {
    expect(InvoiceCalculator::withholding(1042.08, new DateTimeImmutable('2024-02-29')))->toBe(68.33)
        ->and(InvoiceCalculator::withholding(1042.08, new DateTimeImmutable('2024-03-01')))->toBe(93.96);
});
