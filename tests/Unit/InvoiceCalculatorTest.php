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

it('totals: somma + ritenuta 8% se flag attivo', function (): void {
    $calc = new InvoiceCalculator;

    $without = $calc->totals(1000.0, 2.0, 40.08, 0.0, false);
    expect($without['total'])->toBe(1042.08)
        ->and($without['withholding_amount'])->toBe(0.0)
        ->and($without['net_amount'])->toBe(1042.08);

    $with = $calc->totals(1000.0, 2.0, 40.08, 0.0, true);
    expect($with['total'])->toBe(1042.08)
        ->and($with['withholding_amount'])->toBe(83.37)
        ->and($with['net_amount'])->toBe(958.71);
});
