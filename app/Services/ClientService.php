<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service per il dominio Client.
 *
 * Owner di: query list + paginazione, mapping verso shape JSON consumata da
 * Inertia, create/update/archive. Il controller resta thin: invoca metodi,
 * non costruisce query né mappa risposte. Tenancy garantita dal global scope
 * [[App\Concerns\BelongsToUser]] sul Model — il service non lo riapplica.
 */
class ClientService
{
    private const PER_PAGE = 50;

    /**
     * Lista paginata + filtrata. Search case-insensitive cross-DB via LOWER()
     * + ESCAPE '\\' esplicito per evitare interpretazioni driver-dipendenti
     * di `%` e `_` dentro l'input utente.
     *
     * Su PostgreSQL il LOWER() non sfrutta gli indici B-tree standard su
     * name/vat_number/tax_code: a volumi grandi serve indice funzionale.
     * Per i volumi attesi (decine di clienti) impatto trascurabile.
     */
    public function paginate(string $search): LengthAwarePaginator
    {
        $search = trim($search);

        return Client::query()
            ->select(['id', 'name', 'vat_number', 'tax_code', 'bank_withholding', 'notes'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->where(function ($q) use ($like): void {
                    $q->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like])
                        ->orWhereRaw("LOWER(vat_number) LIKE ? ESCAPE '\\'", [$like])
                        ->orWhereRaw("LOWER(tax_code) LIKE ? ESCAPE '\\'", [$like]);
                });
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Client $c) => $this->toListItem($c));
    }

    /**
     * @return array<string, mixed>
     */
    public function forShow(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'vat_number' => $client->vat_number,
            'tax_code' => $client->tax_code,
            'bank_withholding' => $client->bank_withholding,
            'notes' => $client->notes,
            'created_at_diff' => $client->created_at?->diffForHumans(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client;
    }

    public function archive(Client $client): void
    {
        $client->delete();
    }

    /**
     * Lookup cliente esistente per identificativo fiscale, dell'utente
     * corrente. Priorità: P.IVA prima, poi CF. Restituisce `null` se
     * entrambi gli argomenti sono nulli o se nessun cliente combacia.
     *
     * Usata dal flusso import XML (matching anteprima + safety-net
     * pre-create). `BelongsToUser` global scope filtra automaticamente
     * sull'utente loggato — il chiamante deve essere in contesto auth.
     */
    public function findByFiscalIds(?string $vatNumber, ?string $taxCode): ?Client
    {
        $vat = $vatNumber !== null && trim($vatNumber) !== '' ? trim($vatNumber) : null;
        $tax = $taxCode !== null && trim($taxCode) !== '' ? trim($taxCode) : null;

        if ($vat === null && $tax === null) {
            return null;
        }

        if ($vat !== null) {
            $byVat = Client::query()->where('vat_number', $vat)->first();

            if ($byVat !== null) {
                return $byVat;
            }
        }

        if ($tax !== null) {
            return Client::query()->where('tax_code', $tax)->first();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'vat_number' => $client->vat_number,
            'tax_code' => $client->tax_code,
            'bank_withholding' => $client->bank_withholding,
            'notes' => $client->notes,
        ];
    }
}
