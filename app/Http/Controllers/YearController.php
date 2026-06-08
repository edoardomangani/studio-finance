<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\OpenYear;
use App\Concerns\FlashesToast;
use App\Exceptions\YearAlreadyOpenException;
use App\Http\Requests\OpenYearRequest;
use App\Services\DeadlineService;
use App\Services\YearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Anni: vista pluriennale, wizard di apertura, vista anno.
 *
 * Thin controller: query/mapping in [[YearService]], piano editabile in
 * [[App\Services\YearOpeningPlanner]], transazione in
 * [[App\Actions\Studiofinance\OpenYear]]. Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class YearController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly YearService $years) {}

    /**
     * Ingresso della sezione Anni: atterra sull'anno corrente (cockpit) se ne
     * esiste uno aperto, altrimenti mostra la vista di confronto pluriennale
     * (che porta l'empty state e la CTA "Apri il primo anno"). La sidebar punta
     * qui; lo switcher e il link "Confronto anni" della vista anno coprono la
     * navigazione tra anni.
     */
    public function index(): RedirectResponse|Response
    {
        $current = $this->years->currentYear();

        if ($current !== null) {
            return redirect()->route('years.show', $current);
        }

        return $this->compare();
    }

    /**
     * Vista di confronto pluriennale: una riga per anno (KPI in arrivo). È la
     * vista "report" secondaria, raggiunta dal link nella vista anno; serve
     * anche da empty state quando non c'è ancora alcun anno.
     */
    public function compare(): Response
    {
        return Inertia::render('years/Compare', [
            'years' => $this->years->list(),
        ]);
    }

    /**
     * Wizard di apertura anno: pagina dedicata (non più dialog). Il piano
     * editabile è caricato dalla pagina via `years.plan` (JSON) al mount e a
     * ogni cambio anno, così l'edit degli step sopravvive al ricalcolo.
     */
    public function create(): Response
    {
        return Inertia::render('years/Create', [
            // Anno proposto di default all'apertura del wizard.
            'suggestedYear' => $this->years->suggestedYear(),
        ]);
    }

    /**
     * Piano editabile (spese da template + scadenze + cross-year) per un
     * anno, in JSON. Consumato dal wizard-dialog su `/anni`, che lo carica in
     * modo asincrono all'apertura e a ogni cambio anno (`?year=YYYY`). Una
     * visita Inertia chiuderebbe il dialog, quindi qui torniamo JSON puro.
     */
    public function plan(Request $request): JsonResponse
    {
        $candidate = (int) $request->query('year', '0');
        $year = $candidate >= 1990 ? $candidate : $this->years->suggestedYear();

        return response()->json($this->years->plan($request->user(), $year));
    }

    public function store(OpenYearRequest $request, OpenYear $openYear): RedirectResponse
    {
        try {
            $year = $openYear($request->user(), $this->planFromRequest($request));
        } catch (YearAlreadyOpenException $e) {
            return back()->withErrors(['year' => $e->getMessage()]);
        }

        $this->flashSuccess("Anno {$year->year} aperto.");

        return to_route('years.show', $year->year);
    }

    public function show(int $year, DeadlineService $deadlines): Response
    {
        $yearModel = $this->years->findByYear($year);

        return Inertia::render('years/Show', [
            'year' => $this->years->forShow($yearModel),
            // Opzioni per i form di scadenza ad-hoc / pagamento manuale del
            // cockpit, SCOPATE su quest'anno: dentro l'anno X si vedono solo le
            // spese (e l'anno) di X, non l'elenco pluriennale della lista globale.
            'annualExpenses' => $this->years->expensePickerForYear($yearModel),
            'yearOptions' => [['id' => $yearModel->id, 'year' => $yearModel->year]],
            'kindOptions' => $deadlines->kindOptions(),
        ]);
    }

    /**
     * Trasforma il payload validato del wizard nel "piano" atteso da
     * [[OpenYear]]: tiene solo le voci incluse e normalizza le scadenze.
     * Le scadenze di pagamento la cui spesa è stata esclusa vengono saltate
     * dall'action (spesa target non risolvibile).
     *
     * @return array<string, mixed>
     */
    private function planFromRequest(OpenYearRequest $request): array
    {
        $validated = $request->validated();

        $expenses = collect($validated['expenses'])
            ->filter(fn (array $row): bool => (bool) $row['included'])
            ->map(fn (array $row): array => [
                'expense_item_id' => $row['expense_item_id'] ?? null,
                'name' => $row['name'],
                'calculation_type' => $row['calculation_type'],
                'is_pension_contribution' => $row['is_pension_contribution'] ?? false,
                'rate' => $row['rate'] ?? null,
                'minimum' => $row['minimum'] ?? null,
                'maximum' => $row['maximum'] ?? null,
                'amount' => $row['amount'] ?? null,
                'previous_year_credit' => null,
            ])
            ->values()
            ->all();

        $deadlines = collect($validated['deadlines'])
            ->map(fn (array $row): array => [
                'recurring_deadline_id' => $row['recurring_deadline_id'] ?? null,
                'name' => $row['name'],
                'due_at' => $row['due_at'],
                'kind' => $row['kind'],
                'expense_item_id' => $row['expense_item_id'] ?? null,
                'expense_year_offset' => $row['expense_year_offset'],
                'quota_type' => $row['quota_type'] ?? null,
            ])
            ->all();

        return [
            'year' => (int) $validated['year'],
            'profitability_coefficient' => (float) $validated['profitability_coefficient'],
            'note' => $validated['note'] ?? null,
            'expenses' => $expenses,
            'deadlines' => $deadlines,
        ];
    }
}
