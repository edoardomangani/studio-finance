<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD clienti.
 *
 * Tenancy via [[App\Concerns\BelongsToUser]]: route-model binding fa 404 se
 * il client non appartiene all'utente.
 *
 * Soft delete: "archivia". Cliente con fatture attive non potrà essere
 * archiviato (vincolo RB14, enforced quando arriva l'entità Fattura in
 * Fase 4 — per ora il delete passa sempre).
 */
class ClientController extends Controller
{
    use FlashesToast;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $clients = Client::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->where(function ($q) use ($like): void {
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(vat_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(tax_code) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'vat_number' => $c->vat_number,
                'tax_code' => $c->tax_code,
                'bank_withholding' => $c->bank_withholding,
                'notes' => $c->notes,
            ])
            ->values()
            ->all();

        return Inertia::render('clients/Index', [
            'clients' => $clients,
            'search' => $search,
        ]);
    }

    public function show(Client $client): Response
    {
        return Inertia::render('clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'vat_number' => $client->vat_number,
                'tax_code' => $client->tax_code,
                'bank_withholding' => $client->bank_withholding,
                'notes' => $client->notes,
                'created_at_diff' => $client->created_at?->diffForHumans(),
            ],
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        $this->flashSuccess('Cliente creato.');

        return to_route('clients.show', $client);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        $this->flashSuccess('Cliente aggiornato.');

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        $this->flashSuccess('Cliente archiviato.');

        return to_route('clients.index');
    }
}
