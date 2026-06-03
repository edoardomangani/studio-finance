<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service per il dominio Invoice.
 *
 * Owner di: query list + paginazione, mapping verso shape JSON consumata da
 * Inertia, create/update/archive. Il controller resta thin: invoca metodi,
 * non costruisce query né mappa risposte. Tenancy garantita dal global scope
 * [[App\Concerns\BelongsToUser]] sul Model.
 */
class InvoiceService
{
    private const PER_PAGE = 50;

    public function __construct(private readonly InvoiceCalculator $calculator) {}

    /**
     * Lista paginata. Filtri opzionali: search testuale (numero o nome
     * cliente), anno di emissione, cliente, ritenuta (tri-state:
     * null = tutte, true = solo con ritenuta, false = solo senza).
     * Eager-load del cliente (id + name) per evitare N+1 sul mapping.
     *
     * Search case-insensitive cross-DB via LOWER + ESCAPE '\\' esplicito
     * per evitare interpretazioni driver-dipendenti di `%` e `_`.
     *
     * @param  array{search?: string|null, year?: int|null, client_id?: int|null, withholding?: bool|null}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';

        return Invoice::query()
            ->with('client:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->where(function ($q) use ($like): void {
                    $q->whereRaw("LOWER(number) LIKE ? ESCAPE '\\'", [$like])
                        ->orWhereHas('client', function ($cq) use ($like): void {
                            $cq->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]);
                        });
                });
            })
            ->when(
                ! empty($filters['year']),
                fn ($q) => $q->where(function ($q) use ($filters): void {
                    foreach ((array) $filters['year'] as $year) {
                        $q->orWhereYear('issued_at', $year);
                    }
                }),
            )
            ->when(
                isset($filters['client_id']) && $filters['client_id'] !== null,
                fn ($q) => $q->where('client_id', $filters['client_id']),
            )
            ->when(
                isset($filters['withholding']) && $filters['withholding'] !== null,
                fn ($q) => $q->where('bank_withholding', $filters['withholding']),
            )
            ->orderByDesc('issued_at')
            ->orderByDesc('number_sort')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Invoice $i) => $this->toListItem($i));
    }

    /**
     * Lista degli anni in cui esistono fatture, in ordine DESC. Serve a
     * popolare il filtro anno della Index senza imporre un range fisso.
     *
     * @return array<int>
     */
    public function availableYears(): array
    {
        // Cross-DB year extraction. Driver supportati dal progetto:
        // PostgreSQL (prod) + SQLite (test). MySQL non è nel piano ma se
        // venisse aggiunto in futuro userebbe `YEAR(issued_at)`; meglio
        // fallire forte ora con un errore esplicito che generare SQL
        // sbagliato silenziosamente.
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        $yearExpr = match ($connection) {
            'sqlite' => "CAST(strftime('%Y', issued_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(YEAR FROM issued_at)::int',
            default => throw new \RuntimeException(
                "InvoiceService::availableYears() non supporta driver '{$connection}'."
            ),
        };

        return Invoice::query()
            ->selectRaw("DISTINCT {$yearExpr} as year")
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->all();
    }

    /**
     * Tutti i clienti attivi dell'utente, shape minimale per ClientPicker
     * (id, name, vat_number, tax_code, bank_withholding). Filtro client-side
     * sul frontend perché volumi attesi sono bassi (~100 clienti max).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function clientsForPicker(): Collection
    {
        return Client::query()
            ->select(['id', 'name', 'vat_number', 'tax_code', 'bank_withholding'])
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'vat_number' => $c->vat_number,
                'tax_code' => $c->tax_code,
                'bank_withholding' => $c->bank_withholding,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forShow(Invoice $invoice): array
    {
        $invoice->loadMissing('client:id,name,vat_number,tax_code,bank_withholding');

        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'issued_at' => $invoice->issued_at?->format('Y-m-d'),
            'amount' => (float) $invoice->amount,
            'inarcassa_amount' => (float) $invoice->inarcassa_amount,
            'stamp_amount' => (float) $invoice->stamp_amount,
            'art_15_amount' => (float) $invoice->art_15_amount,
            'bank_withholding' => $invoice->bank_withholding,
            'total' => (float) $invoice->total,
            'withholding_amount' => (float) $invoice->withholding_amount,
            'client' => [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
                'vat_number' => $invoice->client->vat_number,
                'tax_code' => $invoice->client->tax_code,
                'bank_withholding' => $invoice->client->bank_withholding,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($this->canonicalize($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($this->canonicalize($data));

        return $invoice;
    }

    /**
     * Canonicalizza gli importi calcolati (stamp, inarcassa) via
     * [[InvoiceCalculator::canonicalize]]: i valori inviati che
     * corrispondono ai default vengono ricalcolati canonici, gli
     * override utente sono preservati. Difesa contro payload manomessi
     * e drift sub-cent.
     *
     * `total`/`withholding_amount`/`net_amount` sono derivati a render
     * via accessor su [[Invoice]], non vengono persistiti — non
     * propaghiamo le chiavi del calculator output.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function canonicalize(array $data): array
    {
        $computed = $this->calculator->canonicalize($data);

        // Override mirato: solo i 2 campi canonicalizzati, il resto del
        // payload originale (number, issued_at, client_id, ecc.) passa
        // intatto. `Invoice::$fillable` filtra eventuali chiavi extra.
        $data['stamp_amount'] = $computed['stamp_amount'];
        $data['inarcassa_amount'] = $computed['inarcassa_amount'];

        return $data;
    }

    public function archive(Invoice $invoice): void
    {
        $invoice->delete();
    }

    /**
     * Storico fatturato di un cliente, ordinato per data DESC. Shape uguale
     * a [[toListItem]] ma senza il blocco `client` (è già il cliente
     * corrente) — risparmio di payload, no info ridondante in UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForClient(Client $client): array
    {
        return Invoice::query()
            ->where('client_id', $client->id)
            ->orderByDesc('issued_at')
            ->orderByDesc('number_sort')
            ->orderByDesc('id')
            ->get(['id', 'number', 'issued_at', 'amount', 'inarcassa_amount', 'stamp_amount', 'art_15_amount', 'bank_withholding'])
            ->map(fn (Invoice $i) => [
                'id' => $i->id,
                'number' => $i->number,
                'issued_at' => $i->issued_at?->format('Y-m-d'),
                'amount' => (float) $i->amount,
                'total' => (float) $i->total,
                'bank_withholding' => $i->bank_withholding,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'issued_at' => $invoice->issued_at?->format('Y-m-d'),
            'amount' => (float) $invoice->amount,
            'inarcassa_amount' => (float) $invoice->inarcassa_amount,
            'stamp_amount' => (float) $invoice->stamp_amount,
            'art_15_amount' => (float) $invoice->art_15_amount,
            'total' => (float) $invoice->total,
            'bank_withholding' => $invoice->bank_withholding,
            'client' => [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
            ],
        ];
    }
}
