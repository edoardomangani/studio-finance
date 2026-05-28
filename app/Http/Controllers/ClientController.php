<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\InvoiceService;
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
 * Archiviazione (soft delete): ammessa anche se il cliente ha fatture
 * collegate. Le fatture restano intatte (RB2). L'hard-delete invece è
 * bloccato dalla FK `invoices.client_id` `restrictOnDelete()` a livello
 * DB, ma non è esposto da nessuna route.
 */
class ClientController extends Controller
{
    use FlashesToast;

    public function __construct(
        private readonly ClientService $clients,
        private readonly InvoiceService $invoices,
    ) {}

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
            'invoices' => $this->invoices->listForClient($client),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create($request->validated());

        $this->flashSuccess('Cliente creato.');

        // Modalità inline (usata da ClientPicker dentro il form fattura): non
        // navigare a /clients/{id}, torna indietro flashando l'id per
        // permettere al chiamante di auto-selezionare il nuovo cliente.
        // Inertia::flash() è il pattern v3 (one-shot prop nel prossimo
        // response), allineato a flashSuccess.
        if ($request->boolean('_inline')) {
            Inertia::flash('new_client_id', $client->id);

            return back();
        }

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
