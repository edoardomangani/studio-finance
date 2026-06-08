<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\YearAlreadyOpenException;
use App\Models\AnnualExpense;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use Illuminate\Support\Facades\DB;

/**
 * Apertura di un anno in un'unica transazione (F6): crea l'Anno, le spese
 * annuali (istanze dei template), le scadenze con date calcolate, i pagamenti
 * `planned` 1:1 per le scadenze di pagamento e — quando una scadenza paga la
 * spesa dell'anno successivo (Commercialista) — l'Anno N+1 "pre-aperto" con la
 * sola spesa necessaria (RB8/RB10).
 *
 * Idempotenza sulla coppia (year_id, expense_item_id): se l'anno esiste già
 * come pre-aperto, viene completato riusando le istanze esistenti invece di
 * duplicarle. Aprire formalmente un anno già aperto (non pre-aperto) solleva
 * [[YearAlreadyOpenException]].
 *
 * Riceve il "piano" prodotto da [[YearOpeningPlanner]] (eventualmente editato
 * dal wizard: aliquote, date, voci escluse). Il piano è la sola fonte di
 * verità per l'anno N; le spese cross-year su N+1 sono derivate dai template.
 *
 * @phpstan-import-type Plan from YearOpeningPlanner
 */
class OpenYear
{
    /**
     * @param  Plan  $plan
     *
     * @throws YearAlreadyOpenException
     */
    public function __invoke(User $user, array $plan): Year
    {
        return DB::transaction(function () use ($user, $plan): Year {
            $yearNumber = (int) $plan['year'];
            $coefficient = (float) $plan['profitability_coefficient'];

            $year = $this->resolveYear($user, $yearNumber, $coefficient, $plan['note'] ?? null);

            // Spese dell'anno N: autoritative dal piano. updateOrCreate sulla
            // coppia (year_id, expense_item_id) così un'eventuale spesa già
            // presente (anno N era pre-aperto) viene riusata e riallineata ai
            // valori del wizard. Una tantum (expense_item_id null) → create.
            $expensesByItem = [];
            foreach ($plan['expenses'] as $row) {
                $expense = $this->upsertYearExpense($year, $row);
                if ($expense->expense_item_id !== null) {
                    $expensesByItem[$expense->expense_item_id] = $expense;
                }
            }

            foreach ($plan['deadlines'] as $row) {
                $this->createDeadline($user, $year, $coefficient, $row, $expensesByItem);
            }

            return $year;
        });
    }

    /**
     * Risolve l'anno N: riusa il pre-aperto completandolo, blocca se già
     * aperto formalmente, altrimenti lo crea.
     */
    private function resolveYear(User $user, int $yearNumber, float $coefficient, ?string $note): Year
    {
        $existing = $user->years()->where('year', $yearNumber)->first();

        if ($existing !== null && ! $existing->pre_opened) {
            throw new YearAlreadyOpenException($yearNumber);
        }

        if ($existing !== null) {
            $existing->update([
                'profitability_coefficient' => $coefficient,
                'pre_opened' => false,
                'note' => $note ?? $existing->note,
            ]);

            return $existing;
        }

        return $user->years()->create([
            'year' => $yearNumber,
            'profitability_coefficient' => $coefficient,
            'pre_opened' => false,
            'note' => $note,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertYearExpense(Year $year, array $row): AnnualExpense
    {
        $attributes = [
            'name' => $row['name'],
            'calculation_type' => $row['calculation_type'],
            'kind' => $row['kind'],
            'rate' => $row['rate'] ?? null,
            'minimum' => $row['minimum'] ?? null,
            'maximum' => $row['maximum'] ?? null,
            'amount' => $row['amount'] ?? null,
            'previous_year_credit' => $row['previous_year_credit'] ?? null,
        ];

        $itemId = $row['expense_item_id'] ?? null;

        if ($itemId === null) {
            return $year->annualExpenses()->create($attributes);
        }

        return $year->annualExpenses()->updateOrCreate(
            ['expense_item_id' => $itemId],
            $attributes,
        );
    }

    /**
     * Crea una scadenza e, se di pagamento, risolve la spesa target e genera
     * il pagamento pianificato 1:1. Salta le scadenze di pagamento la cui
     * spesa N è stata esclusa dal wizard.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, AnnualExpense>  $expensesByItem  spese anno N per expense_item_id
     */
    private function createDeadline(User $user, Year $year, float $coefficient, array $row, array $expensesByItem): void
    {
        $kind = DeadlineKind::from($row['kind']);

        if ($kind !== DeadlineKind::Payment) {
            $year->deadlines()->create([
                'recurring_deadline_id' => $row['recurring_deadline_id'] ?? null,
                'name' => $row['name'],
                'due_at' => $row['due_at'],
                'kind' => $kind,
                'status' => DeadlineStatus::Open,
            ]);

            return;
        }

        $itemId = $row['expense_item_id'] ?? null;
        $expense = $this->resolveTargetExpense(
            $user,
            $year,
            $coefficient,
            $itemId,
            $row['expense_year_offset'] ?? 'current',
            $expensesByItem,
        );

        // Spesa N esclusa dal wizard → niente scadenza di pagamento orfana.
        if ($expense === null) {
            return;
        }

        $deadline = $year->deadlines()->create([
            'recurring_deadline_id' => $row['recurring_deadline_id'] ?? null,
            'name' => $row['name'],
            'due_at' => $row['due_at'],
            'kind' => DeadlineKind::Payment,
            'annual_expense_id' => $expense->id,
            'quota_type' => $row['quota_type'] ?? null,
            'status' => DeadlineStatus::Open,
        ]);

        // Pagamento pianificato 1:1: importo e data vuoti finché non registrato.
        $deadline->payment()->create([
            'user_id' => $user->id,
            'annual_expense_id' => $expense->id,
            'description' => $row['name'],
            'status' => PaymentStatus::Planned,
        ]);
    }

    /**
     * Risolve la spesa pagata da una scadenza:
     * - offset `current` → spesa dell'anno N (null se esclusa dal wizard).
     * - offset `next` → spesa dell'anno N+1, creando se serve l'anno N+1
     *   pre-aperto e la spesa dal template (cross-year, RB8). firstOrCreate:
     *   non sovrascrive valori se N+1 è già stato aperto formalmente.
     *
     * @param  array<int, AnnualExpense>  $expensesByItem
     */
    private function resolveTargetExpense(User $user, Year $year, float $coefficient, ?int $itemId, string $expenseYearOffset, array $expensesByItem): ?AnnualExpense
    {
        if ($itemId === null) {
            return null;
        }

        if ($expenseYearOffset !== 'next') {
            return $expensesByItem[$itemId] ?? null;
        }

        $nextYear = $this->findOrCreatePreOpenedYear($user, $year->year + 1, $coefficient);
        $item = $user->expenseItems()->withTrashed()->find($itemId);

        if ($item === null) {
            return null;
        }

        $attributes = YearOpeningPlanner::expenseRowFromItem($item);
        unset($attributes['expense_item_id'], $attributes['previous_year_credit']);

        return $nextYear->annualExpenses()->firstOrCreate(
            ['expense_item_id' => $itemId],
            $attributes,
        );
    }

    /**
     * Anno N+1 per il cross-year: riusa quello esistente (aperto o pre-aperto)
     * o ne crea uno nuovo pre-aperto con la sola spesa che verrà aggiunta dal
     * chiamante.
     */
    private function findOrCreatePreOpenedYear(User $user, int $yearNumber, float $coefficient): Year
    {
        return $user->years()->firstOrCreate(
            ['year' => $yearNumber],
            [
                'profitability_coefficient' => $coefficient,
                'pre_opened' => true,
            ],
        );
    }
}
