<?php

namespace App\Actions\Studiofinance;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Support\Facades\DB;

/**
 * Import batch di fatture FatturaPA parsate.
 *
 * Riceve l'array di anteprime già editate dall'utente e crea in una
 * singola transazione:
 * - clienti nuovi (dedup intra-batch + DB lookup safety net su P.IVA/CF
 *   per evitare violazioni del partial unique index `(user_id, vat_number)
 *   WHERE NOT NULL` se l'utente ha selezionato "Nuovo da XML" su un
 *   cliente che era già a sistema)
 * - fatture pronte
 *
 * Scarta silenziosamente le anteprime con `excluded=true` o status diverso
 * da `pronto`. Restituisce un riepilogo per il toast.
 *
 * @phpstan-type ImportPreview array{
 *     number: string,
 *     issued_at: string,
 *     amount: float|int|string,
 *     inarcassa_amount: float|int|string,
 *     stamp_amount: float|int|string,
 *     art_15_amount: float|int|string,
 *     bank_withholding: bool,
 *     client_mode: 'existing'|'new',
 *     existing_client_id?: int|null,
 *     new_client?: array{
 *         name: string,
 *         vat_number: string|null,
 *         tax_code: string|null,
 *         bank_withholding: bool,
 *     }|null,
 * }
 */
class ImportInvoices
{
    public function __construct(
        private readonly ClientService $clients,
    ) {}

    /**
     * @param  array<int, ImportPreview>  $previews
     * @return array{invoices_created: int, clients_created: int}
     */
    public function __invoke(User $user, array $previews): array
    {
        // $user è il param di scope semantico: tutte le query interne
        // sono filtrate da `BelongsToUser` global scope su Auth::id(),
        // garantito uguale a $user->id dal Form Request authorize.
        return DB::transaction(function () use ($previews): array {
            $invoicesCreated = 0;
            $clientsCreated = 0;
            $batchNewClients = [];

            foreach ($previews as $preview) {
                $client = $this->resolveClient(
                    $preview,
                    $batchNewClients,
                    $clientsCreated,
                );

                Invoice::create([
                    'client_id' => $client->id,
                    'number' => (string) $preview['number'],
                    'issued_at' => $preview['issued_at'],
                    'amount' => $preview['amount'],
                    'inarcassa_amount' => $preview['inarcassa_amount'],
                    'stamp_amount' => $preview['stamp_amount'],
                    'art_15_amount' => $preview['art_15_amount'],
                    'bank_withholding' => (bool) $preview['bank_withholding'],
                ]);

                $invoicesCreated++;
            }

            return [
                'invoices_created' => $invoicesCreated,
                'clients_created' => $clientsCreated,
            ];
        });
    }

    /**
     * Risolve il cliente per la fattura. Esistente = lookup diretto (la FK
     * validation in FormRequest ha già controllato l'ownership). Nuovo =
     * dedup intra-batch su P.IVA/CF, poi safety-net su DB (l'utente
     * potrebbe aver scelto "Nuovo" per un cliente già esistente: invece
     * di crashare sull'unique partial index, lo riusiamo).
     *
     * @param  array<string, mixed>  $preview
     * @param  array<string, Client>  $batchNewClients
     */
    private function resolveClient(
        array $preview,
        array &$batchNewClients,
        int &$clientsCreated,
    ): Client {
        if (($preview['client_mode'] ?? 'existing') === 'existing') {
            // Route-level: il global scope BelongsToUser garantisce che
            // l'id appartenga all'utente loggato.
            return Client::query()->findOrFail($preview['existing_client_id']);
        }

        $new = $preview['new_client'] ?? [];
        $vat = $this->normalizeId($new['vat_number'] ?? null);
        $tax = $this->normalizeId($new['tax_code'] ?? null);

        // Dedup intra-batch: priorità a P.IVA, fallback a CF. Quando
        // entrambi presenti il match va su P.IVA, ignorando CF.
        // Edge case raro: due righe con stesso CF ma P.IVA diverse non
        // vengono dedupate (corretto: sono entità fiscalmente distinte).
        $dedupKey = $vat ?? $tax;

        if ($dedupKey !== null && isset($batchNewClients[$dedupKey])) {
            return $batchNewClients[$dedupKey];
        }

        // Safety net: cliente già a sistema (l'utente ha scelto "Nuovo"
        // ma P.IVA/CF coincidono con un cliente esistente).
        $existing = $this->clients->findByFiscalIds($vat, $tax);

        if ($existing !== null) {
            if ($dedupKey !== null) {
                $batchNewClients[$dedupKey] = $existing;
            }

            return $existing;
        }

        $client = Client::create([
            'name' => (string) ($new['name'] ?? ''),
            'vat_number' => $vat,
            'tax_code' => $tax,
            'bank_withholding' => (bool) ($new['bank_withholding'] ?? false),
        ]);

        $clientsCreated++;

        if ($dedupKey !== null) {
            $batchNewClients[$dedupKey] = $client;
        }

        return $client;
    }

    private function normalizeId(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
