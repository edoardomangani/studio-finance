<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\Year;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * Pagina di scadenze (cronologica, più recenti per data prima) con i filtri
     * applicati e l'importo previsto per riga.
     *
     * @param  array{search?: string, status?: ?string, kind?: ?string, year?: ?int}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $status = isset($filters['status']) ? DeadlineStatus::tryFrom((string) $filters['status']) : null;
        $kind = isset($filters['kind']) ? DeadlineKind::tryFrom((string) $filters['kind']) : null;
        $year = $filters['year'] ?? null;

        $paginator = Deadline::query()
            ->with(['payment', 'annualExpense.year', 'year'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]);
            })
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when($kind !== null, fn ($q) => $q->where('kind', $kind))
            ->when($year !== null, fn ($q) => $q->whereHas('year', fn ($yq) => $yq->where('year', $year)))
            ->orderByDesc('due_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $context = $this->contextBuilder->build($paginator->getCollection());

        return $paginator->through(fn (Deadline $d): array => $this->toListItem($d, $context));
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
     * @return array<int, array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return array_map(
            fn (DeadlineStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
            DeadlineStatus::cases(),
        );
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
