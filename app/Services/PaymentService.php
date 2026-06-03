<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Payment;
use App\Models\Year;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service per il dominio Payment: vista pluriennale dei pagamenti (RB9) con
 * filtri (stato, anno spesa, anno data effettiva) e i dati di supporto al form
 * di registrazione manuale extra-scadenza (F8). Tenancy via global scope
 * [[App\Concerns\BelongsToUser]]; la persistenza del pagamento manuale vive in
 * [[App\Actions\Studiofinance\RegisterManualPayment]].
 */
class PaymentService
{
    private const PER_PAGE = 50;

    /**
     * Pagina di pagamenti (pagati prima, poi pianificati senza data) con i
     * filtri applicati. I due "anni rilevanti" (RB9): l'anno della spesa
     * (`expense_year`) e l'anno della data di cassa (`paid_year`).
     *
     * Faccette multi-select (array): nessuna selezione = nessun filtro.
     *
     * @param  array{search?: string, status?: list<string>, expense_year?: list<int>, paid_year?: list<int>}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $statuses = $filters['status'] ?? [];
        $expenseYears = $filters['expense_year'] ?? [];
        $paidYears = $filters['paid_year'] ?? [];

        return Payment::query()
            ->with(['annualExpense.year'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->whereRaw("LOWER(description) LIKE ? ESCAPE '\\'", [$like]);
            })
            ->when($statuses !== [], fn ($q) => $q->whereIn('status', $statuses))
            ->when($expenseYears !== [], fn ($q) => $q->whereHas(
                'annualExpense',
                fn ($eq) => $eq->whereHas('year', fn ($yq) => $yq->whereIn('year', $expenseYears)),
            ))
            ->when($paidYears !== [], fn ($q) => $q->where(function ($q) use ($paidYears): void {
                foreach ($paidYears as $y) {
                    $q->orWhereYear('paid_at', $y);
                }
            }))
            // Pagati (data valorizzata) prima; i pianificati senza data in fondo.
            // `paid_at IS NULL` → 0/1 portabile SQLite/Postgres (NULLS LAST manuale).
            ->orderByRaw('paid_at IS NULL')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Payment $p): array => $this->toListItem($p));
    }

    /**
     * Anni di riferimento (spesa) dell'utente, per il filtro (DESC).
     *
     * @return array<int, int>
     */
    public function availableExpenseYears(): array
    {
        return Year::query()->orderByDesc('year')->pluck('year')->all();
    }

    /**
     * Anni in cui cade la data di cassa (da paid_at), per il filtro. Estrazione
     * anno in PHP per portabilità DB (volumi piccoli).
     *
     * @return array<int, int>
     */
    public function availablePaidYears(): array
    {
        return Payment::query()
            ->whereNotNull('paid_at')
            ->distinct()
            ->pluck('paid_at')
            ->map(fn ($date): int => (int) $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return array_map(
            fn (PaymentStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
            PaymentStatus::cases(),
        );
    }

    /**
     * Spese annuali (tutti gli anni) per l'autocomplete del pagamento manuale.
     * Solo quelle attive (no archiviate): un pagamento manuale si registra
     * sempre contro una spesa viva.
     *
     * @return array<int, array{id: int, name: string, year: int}>
     */
    public function annualExpensesForPicker(): array
    {
        return AnnualExpense::query()
            ->with('year:id,year')
            ->orderByDesc('year_id')
            ->orderBy('name')
            ->get(['id', 'year_id', 'name'])
            ->map(fn (AnnualExpense $e): array => [
                'id' => $e->id,
                'name' => $e->name,
                'year' => $e->year->year,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'description' => $payment->description,
            'annual_expense_id' => $payment->annual_expense_id,
            'annual_expense_name' => $payment->annualExpense?->name,
            'expense_year' => $payment->annualExpense?->year?->year,
            'amount' => $payment->amount !== null ? (float) $payment->amount : null,
            'paid_at' => $payment->paid_at?->toDateString(),
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            // deadline_id null = pagamento manuale extra-scadenza (F8).
            'is_manual' => $payment->deadline_id === null,
        ];
    }
}
