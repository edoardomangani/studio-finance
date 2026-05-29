<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\OpenYear;
use App\Concerns\FlashesToast;
use App\Exceptions\YearAlreadyOpenException;
use App\Http\Requests\OpenYearRequest;
use App\Models\Year;
use App\Services\YearService;
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

    public function index(): Response
    {
        return Inertia::render('years/Index', [
            'years' => $this->years->list(),
        ]);
    }

    /**
     * Step 1-3 del wizard. `?year=YYYY` preseleziona l'anno (deep link da
     * empty state dashboard / "Apri anno corrente"); altrimenti propone
     * l'anno suggerito.
     */
    public function openForm(Request $request): Response
    {
        $candidate = (int) $request->query('year', '0');
        $year = $candidate >= 1990 ? $candidate : $this->years->suggestedYear();

        return Inertia::render('years/OpenWizard', [
            'plan' => $this->years->plan($request->user(), $year),
        ]);
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

    public function show(int $year): Response
    {
        $model = Year::query()->where('year', $year)->firstOrFail();

        return Inertia::render('years/Show', [
            'year' => $this->years->forShow($model),
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
