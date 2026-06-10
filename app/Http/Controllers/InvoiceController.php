<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Concerns\NormalizesFacetFilters;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD fatture manuali.
 *
 * Tenancy via [[App\Concerns\BelongsToUser]]: route-model binding fa 404 se
 * la fattura non appartiene all'utente.
 *
 * Tutta la logica (query, mapping, persistence) vive in [[InvoiceService]];
 * questo controller resta thin: parse request → invoca service → return.
 *
 * Numero univoco per `(user_id, anno_di_emissione)`: cross-field validation
 * gestita in [[App\Concerns\InvoiceValidationRules]]::invoiceAfterValidation().
 */
class InvoiceController extends Controller
{
    use FlashesToast, NormalizesFacetFilters;

    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        // Anno è una faccetta multi-select (array); cliente resta singolo,
        // ritenuta resta tri-state (la UI usa 2 checkbox mappati a true/false/null).
        $year = $this->intArray($request->query('year'));
        $clientId = $this->intOrNull($request->query('client_id'));
        $withholding = $this->triStateOrNull($request->query('withholding'));

        return Inertia::render('invoices/Index', [
            'invoices' => $this->invoices->paginate([
                'search' => $search,
                'year' => $year,
                'client_id' => $clientId,
                'withholding' => $withholding,
            ]),
            'filters' => [
                'search' => $search,
                'year' => $year,
                'client_id' => $clientId,
                'withholding' => $withholding,
            ],
            'availableYears' => $this->invoices->availableYears(),
            'clientsForFilter' => $this->invoices->clientsForPicker(),
        ]);
    }

    /** Helper: query-string → int, vuoto/null → null. Evita ripetizione
     *  del pattern `$x !== null && $x !== '' ? (int) $x : null`. */
    private function intOrNull(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return is_numeric($raw) ? (int) $raw : null;
    }

    /** Helper tri-state: "1"/"true" → true, "0"/"false" → false, altro → null.
     *  Per filtri "Tutte / Sì / No" che diventano querystring `?withholding=1`. */
    private function triStateOrNull(mixed $raw): ?bool
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($raw === '1' || $raw === 1 || $raw === true || $raw === 'true') {
            return true;
        }

        if ($raw === '0' || $raw === 0 || $raw === false || $raw === 'false') {
            return false;
        }

        return null;
    }

    /**
     * Form di creazione. Accetta `?client=X` per pre-selezionare un cliente
     * (deep link da `/clients/{id}`). Hardening: l'id viene validato contro
     * la lista clienti dell'utente — se è inesistente, archiviato (soft
     * delete) o di altro utente, non viene passato al form (evita
     * "Cerca cliente…" muto senza spiegazione).
     *
     * Accetta anche `?from=Y` per duplicare una fattura: il form parte
     * precompilato con gli importi/flag della sorgente (numero e data
     * esclusi, vengono reimpostati lato form). Nessuna scrittura a DB: è
     * solo prefill, la fattura nasce al submit. Tenancy: il global scope
     * [[App\Concerns\BelongsToUser]] fa sì che `find()` ritorni null per
     * fatture di altri utenti → sourceInvoice resta null (no IDOR).
     */
    public function create(Request $request): Response
    {
        $clients = $this->invoices->clientsForPicker();
        $candidate = $this->intOrNull($request->query('client'));
        $preselectedClientId = $candidate !== null && $clients->contains('id', $candidate)
            ? $candidate
            : null;

        return Inertia::render('invoices/Create', [
            'clients' => $clients,
            'preselectedClientId' => $preselectedClientId,
            'sourceInvoice' => $this->invoices->forDuplication(
                $this->intOrNull($request->query('from')),
            ),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create($request->validated());

        $this->flashSuccess('Fattura creata.');

        return to_route('invoices.show', $invoice);
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('invoices/Show', [
            'invoice' => $this->invoices->forShow($invoice),
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        return Inertia::render('invoices/Edit', [
            'invoice' => $this->invoices->forShow($invoice),
            'clients' => $this->invoices->clientsForPicker(),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->update($invoice, $request->validated());

        $this->flashSuccess('Fattura aggiornata.');

        return to_route('invoices.show', $invoice);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->invoices->archive($invoice);

        $this->flashSuccess('Fattura archiviata.');

        // back(): la fattura è archiviabile dalla lista e dalla vista anno →
        // si torna alla superficie di partenza.
        return back();
    }
}
