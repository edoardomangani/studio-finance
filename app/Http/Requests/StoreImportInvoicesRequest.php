<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Conferma import batch di fatture (dopo che l'utente ha rivisto le
 * anteprime). Riceve l'array `previews` filtrato (solo i pronti).
 *
 * Vincoli per riga:
 * - Campi fattura: stessi limiti del form manuale (decimal:0,2 ecc.)
 * - `client_mode`: existing | new
 * - Se existing: `existing_client_id` deve appartenere all'utente
 *   (Rule::exists su clients filtrato per user_id, anti-IDOR)
 * - Se new: `new_client` con almeno P.IVA o CF (regole RB2 cliente)
 * - Unicità `(number, anno di issued_at)` per utente, stesso vincolo RB3
 *   del form manuale ([[App\Concerns\InvoiceValidationRules]]). Vale sia
 *   contro fatture già a sistema sia INTRA-BATCH (non si possono importare
 *   2 anteprime con stesso numero+anno nello stesso submit).
 */
class StoreImportInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'previews' => ['required', 'array', 'min:1', 'max:20'],

            'previews.*.number' => ['required', 'string', 'max:64'],
            'previews.*.issued_at' => ['required', 'date'],
            'previews.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'previews.*.inarcassa_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'previews.*.stamp_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'previews.*.art_15_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'previews.*.bank_withholding' => ['required', 'boolean'],

            'previews.*.client_mode' => ['required', 'in:existing,new'],

            // Cliente esistente: required_if + ownership check tramite Rule::exists
            'previews.*.existing_client_id' => [
                'required_if:previews.*.client_mode,existing',
                'nullable',
                Rule::exists((new Client)->getTable(), 'id')->where('user_id', Auth::id()),
            ],

            // Cliente nuovo: nested array required_if mode=new. La regola
            // cross-field "almeno P.IVA o CF" non sta nei rules base perché
            // `required_without` scatterebbe anche su righe con mode=existing
            // (dove new_client è null): la mettiamo in withValidator().
            'previews.*.new_client' => ['required_if:previews.*.client_mode,new', 'nullable', 'array'],
            'previews.*.new_client.name' => ['required_if:previews.*.client_mode,new', 'nullable', 'string', 'min:2', 'max:255'],
            'previews.*.new_client.vat_number' => ['nullable', 'string', 'max:32'],
            'previews.*.new_client.tax_code' => ['nullable', 'string', 'max:32'],
            'previews.*.new_client.bank_withholding' => ['required_if:previews.*.client_mode,new', 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $previews = $this->input('previews', []);

            if (! is_array($previews)) {
                return;
            }

            $this->validateNewClientFiscalIds($validator, $previews);
            $this->validateInvoiceNumberUniqueness($validator, $previews);
        });
    }

    /**
     * Cross-field "almeno P.IVA o CF" per le righe con client_mode=new.
     * Non sta in rules() perché `required_without` scatterebbe anche sulle
     * righe con client_mode=existing (dove new_client è null).
     *
     * @param  array<int, array<string, mixed>>  $previews
     */
    private function validateNewClientFiscalIds(Validator $validator, array $previews): void
    {
        foreach ($previews as $i => $preview) {
            if (($preview['client_mode'] ?? null) !== 'new') {
                continue;
            }

            $vat = trim((string) ($preview['new_client']['vat_number'] ?? ''));
            $tax = trim((string) ($preview['new_client']['tax_code'] ?? ''));

            if ($vat === '' && $tax === '') {
                $validator->errors()->add(
                    "previews.{$i}.new_client.vat_number",
                    'Almeno P.IVA o CF richiesto sul cliente nuovo.',
                );
            }
        }
    }

    /**
     * Unicità `(user_id, number, anno di issued_at)`: stesso vincolo del
     * form manuale (vedi [[App\Concerns\InvoiceValidationRules]]). Due
     * livelli di check:
     * 1. Contro fatture già a sistema (query DB, BelongsToUser global scope
     *    filtra automaticamente sull'utente loggato).
     * 2. Intra-batch: due previews con stessa coppia (number, year) in
     *    questo submit → entrambe segnalate (l'utente vede tutti i conflitti).
     *
     * Errori aggiunti su `previews.{i}.number`: il frontend li mappa già
     * a label leggibili ("Fattura N · Numero") via `humanizeErrorField`.
     *
     * @param  array<int, array<string, mixed>>  $previews
     */
    private function validateInvoiceNumberUniqueness(Validator $validator, array $previews): void
    {
        /** @var array<string, list<int>> $seenInBatch */
        $seenInBatch = [];

        foreach ($previews as $i => $preview) {
            $number = $preview['number'] ?? null;
            $issuedAt = $preview['issued_at'] ?? null;

            // Skip se base rules falliranno già (no falsi positivi su campi vuoti).
            if (! is_string($number) || $number === '' || ! is_string($issuedAt)) {
                continue;
            }

            try {
                $year = Carbon::parse($issuedAt)->year;
            } catch (\Throwable) {
                continue;
            }

            // Check 1 — DB lookup. BelongsToUser global scope su Invoice
            // applica `where user_id = Auth::id()` in automatico.
            $alreadyInDb = Invoice::query()
                ->where('number', $number)
                ->whereYear('issued_at', $year)
                ->exists();

            if ($alreadyInDb) {
                $validator->errors()->add(
                    "previews.{$i}.number",
                    sprintf('«%s» è già usato per il %d.', $number, $year),
                );
            }

            // Check 2 — Tracciamento intra-batch. Accumuliamo gli indici
            // per ogni coppia (year, number); a fine loop chi appare più
            // di una volta è duplicato.
            $key = $year.':'.$number;
            $seenInBatch[$key][] = (int) $i;
        }

        foreach ($seenInBatch as $key => $indexes) {
            if (count($indexes) <= 1) {
                continue;
            }

            [$year, $number] = explode(':', $key, 2);

            foreach ($indexes as $i) {
                $validator->errors()->add(
                    "previews.{$i}.number",
                    sprintf('«%s» è ripetuto più volte in questo import per il %s.', $number, $year),
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // NOTE: il messaggio "almeno P.IVA o CF" non sta qui perché la
        // regola corrispondente vive in `withValidator()` (vedi sopra)
        // dove `$validator->errors()->add(...)` passa già il testo
        // finale all'utente.
        return [
            'previews.required' => 'Nessuna fattura da importare.',
            'previews.max' => 'Massimo 20 fatture per batch.',
            'previews.*.existing_client_id.exists' => 'Cliente selezionato non valido.',
            'previews.*.new_client.name.required_if' => 'Denominazione cliente nuovo obbligatoria.',
        ];
    }
}
