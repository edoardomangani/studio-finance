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
     * Pagina del registro di cassa: solo i pagamenti `paid` (fatti di cassa).
     * I `planned` vivono sulla scadenza (è lei la sorgente di verità del ciclo
     * di vita) e i `not_due` sono uno stato della scadenza, non cassa: nessuno
     * dei due appartiene a questa vista. I due "anni rilevanti" (RB9): l'anno
     * della spesa (`expense_year`) e l'anno della data di cassa (`paid_year`).
     *
     * Faccette multi-select (array): nessuna selezione = nessun filtro.
     *
     * @param  array{search?: string, expense_year?: list<int>, paid_year?: list<int>}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $expenseYears = $filters['expense_year'] ?? [];
        $paidYears = $filters['paid_year'] ?? [];

        return Payment::query()
            ->with(['annualExpense.year'])
            ->where('status', PaymentStatus::Paid)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
                $query->whereRaw("LOWER(description) LIKE ? ESCAPE '\\'", [$like]);
            })
            ->when($expenseYears !== [], fn ($q) => $q->whereHas(
                'annualExpense',
                fn ($eq) => $eq->whereHas('year', fn ($yq) => $yq->whereIn('year', $expenseYears)),
            ))
            ->when($paidYears !== [], fn ($q) => $q->where(function ($q) use ($paidYears): void {
                foreach ($paidYears as $y) {
                    $q->orWhereYear('paid_at', $y);
                }
            }))
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
     * Spese annuali per l'autocomplete del pagamento manuale (delega al model,
     * condivisa con la scadenza ad-hoc).
     *
     * @return array<int, array{id: int, name: string, year: int}>
     */
    public function annualExpensesForPicker(): array
    {
        return AnnualExpense::pickerOptions();
    }

    /**
     * Aggiorna un pagamento manuale (spesa riassegnabile, importo/data/
     * descrizione). Presuppone il guard del controller (solo manuale).
     *
     * @param  array{annual_expense_id: int, description?: ?string, amount: mixed, paid_at: mixed}  $data
     */
    public function update(Payment $payment, array $data): void
    {
        $payment->update([
            'annual_expense_id' => $data['annual_expense_id'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'],
        ]);
    }

    /**
     * Elimina (soft delete) un pagamento manuale. Presuppone il guard del
     * controller (solo manuale): un pagamento da scadenza si gestisce dalla
     * scadenza.
     */
    public function delete(Payment $payment): void
    {
        $payment->delete();
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
            // Sempre valorizzati: il registro mostra solo i pagamenti `paid`.
            'amount' => (float) $payment->amount,
            'paid_at' => $payment->paid_at?->toDateString(),
            // deadline_id null = pagamento manuale extra-scadenza (F8).
            'is_manual' => $payment->deadline_id === null,
        ];
    }
}
