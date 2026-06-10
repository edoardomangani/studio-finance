<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\ExpenseItem;
use App\Models\Year;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service per il dominio Deadline: lista cronologica pluriennale con filtri e
 * importo previsto (suggerimento) calcolato per ogni riga. Il calcolo del
 * previsto è delegato al puro [[DeadlineExpectation]], con i dati pre-caricati
 * da [[DeadlineContextBuilder]] (una volta per pagina). Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class DeadlineService
{
    private const PER_PAGE = 50;

    public function __construct(
        private readonly DeadlineContextBuilder $contextBuilder,
        private readonly DeadlineExpectation $expectation,
    ) {}

    /**
     * Pagina di scadenze con i filtri applicati e l'importo previsto per riga.
     * Ordine per stato: aperte crescente (la più imminente prima), completate e
     * vista "tutte" decrescente (l'ultima gestita prima).
     *
     * Faccette multi-select (array): nessuna selezione = nessun filtro.
     *
     * @param  array{search?: string, state?: ?string, kind?: list<string>, year?: list<int>, due_year?: list<int>, expense_item_id?: list<int>}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        // Stato come gruppo: open = da fare; closed = già gestite (completate +
        // non dovute, "cose fatte"). Toggle primario in UI (resta singolo).
        $state = $filters['state'] ?? null;
        $kinds = $filters['kind'] ?? [];
        // year = anno di riferimento (spesa), due_year = anno della scadenza (due_at).
        $years = $filters['year'] ?? [];
        $dueYears = $filters['due_year'] ?? [];
        $expenseItemIds = $filters['expense_item_id'] ?? [];

        $paginator = Deadline::query()
            ->with(['payment', 'annualExpense.year', 'year'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]);
            })
            ->when($state === 'open', fn ($q) => $q->where('status', DeadlineStatus::Open))
            ->when($state === 'closed', fn ($q) => $q->whereIn('status', [DeadlineStatus::Completed, DeadlineStatus::NotDue]))
            ->when($kinds !== [], fn ($q) => $q->whereIn('kind', $kinds))
            ->when($years !== [], fn ($q) => $q->whereHas('year', fn ($yq) => $yq->whereIn('year', $years)))
            ->when($dueYears !== [], fn ($q) => $q->where(function ($q) use ($dueYears): void {
                foreach ($dueYears as $y) {
                    $q->orWhereYear('due_at', $y);
                }
            }))
            ->when($expenseItemIds !== [], fn ($q) => $q->whereHas('annualExpense', fn ($eq) => $eq->whereIn('expense_item_id', $expenseItemIds)))
            // Aperte = to-do: la più imminente prima (ASC). Completate/tutte =
            // registro: l'ultima gestita prima (DESC). id come tiebreaker nello
            // stesso verso.
            ->orderBy('due_at', $state === 'open' ? 'asc' : 'desc')
            ->orderBy('id', $state === 'open' ? 'asc' : 'desc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $context = $this->contextBuilder->build($paginator->getCollection());

        return $paginator->through(fn (Deadline $d): array => $this->toListItem($d, $context));
    }

    /**
     * Conteggio delle scadenze aperte dell'utente (payment + fulfillment), per
     * il badge in sidebar. Coerente con la lista che apre il link "Scadenze"
     * (`state=open`). Scoping tenant automatico via global scope.
     */
    public function openCount(): int
    {
        return Deadline::query()->where('status', DeadlineStatus::Open)->count();
    }

    /**
     * Anni dell'utente, per il filtro (DESC).
     *
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        return Year::query()->orderByDesc('year')->pluck('year')->all();
    }

    /**
     * Anni in cui cadono le scadenze (da due_at), per il filtro "anno scadenza".
     * Estrazione in PHP per portabilità DB (volumi piccoli).
     *
     * @return array<int, int>
     */
    public function availableDueYears(): array
    {
        // Solo date distinte dal DB (poche righe), poi estrazione anno in PHP:
        // l'estrazione lato DB non è portabile SQLite/Postgres senza raw.
        return Deadline::query()
            ->distinct()
            ->pluck('due_at')
            ->map(fn ($date): int => (int) $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Voci di spesa referenziate dalle scadenze dell'utente, per il filtro.
     * withTrashed: una scadenza può puntare a una voce poi archiviata.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function expenseItems(): array
    {
        return ExpenseItem::withTrashed()
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (ExpenseItem $i): array => ['id' => $i->id, 'name' => $i->name])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function kindOptions(): array
    {
        return array_map(
            fn (DeadlineKind $k): array => ['value' => $k->value, 'label' => $k->label()],
            DeadlineKind::cases(),
        );
    }

    /**
     * Spese annuali per l'autocomplete della scadenza ad-hoc di pagamento
     * (delega al model, condivisa con il pagamento manuale).
     *
     * @return array<int, array{id: int, name: string, year: int}>
     */
    public function annualExpensesForPicker(): array
    {
        return AnnualExpense::pickerOptions();
    }

    /**
     * Anni dell'utente come opzioni {id, year} per la scadenza ad-hoc di
     * adempimento (serve l'id, non solo il numero).
     *
     * @return array<int, array{id: int, year: int}>
     */
    public function yearOptions(): array
    {
        return Year::query()
            ->orderByDesc('year')
            ->get(['id', 'year'])
            ->map(fn (Year $y): array => ['id' => $y->id, 'year' => $y->year])
            ->all();
    }

    /**
     * Aggiorna una scadenza. Nome e data sono sempre modificabili (anche sulle
     * standard). Spesa collegata e previsto manuale si toccano solo su una
     * scadenza ad-hoc di pagamento ancora aperta (cioè il cui pagamento non è
     * `paid`): a pagamento avvenuto la spesa muoverebbe cassa già contabilizzata
     * e il previsto è ormai inutile. Se la spesa cambia, il pagamento
     * pianificato 1:1 la segue, in transazione.
     *
     * @param  array{name: string, due_at: mixed, annual_expense_id?: int|null, manual_expected_amount?: mixed}  $data
     */
    public function update(Deadline $deadline, array $data): void
    {
        DB::transaction(function () use ($deadline, $data): void {
            $deadline->fill([
                'name' => $data['name'],
                'due_at' => $data['due_at'],
            ]);

            if ($this->canEditPaymentDetails($deadline)) {
                if (array_key_exists('annual_expense_id', $data)) {
                    $deadline->annual_expense_id = $data['annual_expense_id'];
                    $deadline->payment?->update(['annual_expense_id' => $data['annual_expense_id']]);
                }

                if (array_key_exists('manual_expected_amount', $data)) {
                    $deadline->manual_expected_amount = $data['manual_expected_amount'];
                }
            }

            $deadline->save();
        });
    }

    /**
     * Archivia (soft delete) una scadenza ad-hoc e il suo pagamento pianificato.
     * Presuppone i guard del controller (solo ad-hoc, pagamento non `paid`).
     */
    public function archive(Deadline $deadline): void
    {
        DB::transaction(function () use ($deadline): void {
            $deadline->payment?->delete();
            $deadline->delete();
        });
    }

    /**
     * Spesa collegata e previsto manuale sono modificabili solo su una scadenza
     * ad-hoc di pagamento il cui pagamento non è ancora stato registrato.
     */
    private function canEditPaymentDetails(Deadline $deadline): bool
    {
        return $deadline->isAdHoc()
            && $deadline->kind === DeadlineKind::Payment
            && ! $deadline->isPaid();
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Deadline $deadline, DeadlineContext $context): array
    {
        return [
            'id' => $deadline->id,
            'name' => $deadline->name,
            'due_at' => $deadline->due_at->toDateString(),
            'kind' => $deadline->kind->value,
            'kind_label' => $deadline->kind->label(),
            'quota_type' => $deadline->quota_type?->value,
            'quota_type_label' => $deadline->quota_type?->label(),
            'status' => $deadline->status->value,
            'status_label' => $deadline->status->label(),
            'year' => $deadline->year->year,
            // Ad-hoc (creata a mano): editabile e archiviabile; le standard
            // usano "non dovuta".
            'is_custom' => $deadline->isAdHoc(),
            'annual_expense_id' => $deadline->annual_expense_id,
            'annual_expense_name' => $deadline->annualExpense?->name,
            'expected_amount' => $this->expectation->for($deadline, $context),
            'payment' => $deadline->payment === null ? null : [
                'id' => $deadline->payment->id,
                'status' => $deadline->payment->status->value,
                'status_label' => $deadline->payment->status->label(),
                'amount' => $deadline->payment->amount !== null ? (float) $deadline->payment->amount : null,
                'paid_at' => $deadline->payment->paid_at?->toDateString(),
            ],
        ];
    }
}
