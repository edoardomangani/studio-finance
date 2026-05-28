<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
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
 * Tutta la logica (query, mapping, persistence) vive in [[ClientService]];
 * questo controller resta thin: parse request → invoca service → return.
 *
 * Soft delete: "archivia". Cliente con fatture attive non potrà essere
 * archiviato (vincolo RB14, enforced quando arriva l'entità Fattura in
 * Fase 4 — per ora il delete passa sempre).
 */
class ClientController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly ClientService $clients) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('clients/Index', [
            'clients' => $this->clients->paginate($search),
            'search' => $search,
        ]);
    }

    public function show(Client $client): Response
    {
        return Inertia::render('clients/Show', [
            'client' => $this->clients->forShow($client),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create($request->validated());

        $this->flashSuccess('Cliente creato.');

        return to_route('clients.show', $client);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->clients->update($client, $request->validated());

        $this->flashSuccess('Cliente aggiornato.');

        return to_route('clients.show', $client);
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->clients->archive($client);

        $this->flashSuccess('Cliente archiviato.');

        return to_route('clients.index');
    }
}
