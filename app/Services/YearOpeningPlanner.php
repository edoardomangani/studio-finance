<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\ExpenseYearOffset;
use App\Models\ExpenseItem;
use App\Models\RecurringDeadline;
use App\Models\User;
use App\Models\Year;
use Illuminate\Support\Carbon;

/**
 * Costruisce il "piano" editabile di apertura di un anno: le spese annuali
 * (copie dei template attivi) e le scadenze (con date calcolate dai template
 * ricorrenti), più la rilevazione del cross-year.
 *
 * Usato sia dal controller per popolare il wizard (anteprima negli step 2-3,
 * F6) sia come base che [[OpenYear]] persiste. Il wizard può sovrascrivere
 * aliquote/quote ed escludere voci prima di chiamare l'action.
 *
 * @phpstan-type ExpenseRow array{expense_item_id: int|null, name: string, calculation_type: string, rate: float|null, minimum: float|null, maximum: float|null, amount: float|null, previous_year_credit: float|null}
 * @phpstan-type DeadlineRow array{recurring_deadline_id: int|null, name: string, due_at: string, kind: string, expense_item_id: int|null, expense_year_offset: string, quota_type: string|null}
 * @phpstan-type Plan array{year: int, profitability_coefficient: float, note: string|null, expenses: list<ExpenseRow>, deadlines: list<DeadlineRow>, cross_year_deadlines: list<string>, next_year: int, next_year_exists: bool, next_year_needs_preopen: bool}
 */
class YearOpeningPlanner
{
    /**
     * @return Plan
     */
    public function plan(User $user, int $year): array
    {
        $coefficient = (float) $user->professionalProfile?->profitability_coefficient;

        /** @var array<int, ExpenseItem> $items chiave = expense_item_id */
        $items = $user->expenseItems()
            ->where('active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->all();

        $expenses = array_values(array_map(
            fn (ExpenseItem $item): array => self::expenseRowFromItem($item),
            $items,
        ));

        $deadlines = [];
        $crossYearDeadlines = [];

        $recurring = $user->recurringDeadlines()
            ->where('active', true)
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('id')
            ->get();

        foreach ($recurring as $rd) {
            // Le scadenze di pagamento devono puntare a una voce di spesa
            // attiva: se il template di spesa è stato disattivato, niente
            // istanza da pagare → salta la scadenza.
            if ($rd->kind === DeadlineKind::Payment
                && ($rd->expense_item_id === null || ! isset($items[$rd->expense_item_id]))) {
                continue;
            }

            $deadlines[] = [
                'recurring_deadline_id' => $rd->id,
                'name' => $rd->name,
                'due_at' => self::dueDate($year, $rd)->format('Y-m-d'),
                'kind' => $rd->kind->value,
                'expense_item_id' => $rd->kind === DeadlineKind::Payment ? $rd->expense_item_id : null,
                'expense_year_offset' => $rd->expense_year_offset->value,
                'quota_type' => $rd->kind === DeadlineKind::Payment ? $rd->quota_type?->value : null,
            ];

            if ($rd->kind === DeadlineKind::Payment
                && $rd->expense_year_offset === ExpenseYearOffset::Next) {
                $crossYearDeadlines[] = $rd->name;
            }
        }

        $nextYear = $year + 1;
        $nextYearExists = $user->years()->where('year', $nextYear)->exists();

        return [
            'year' => $year,
            'profitability_coefficient' => $coefficient,
            'note' => null,
            'expenses' => $expenses,
            'deadlines' => $deadlines,
            'cross_year_deadlines' => $crossYearDeadlines,
            'next_year' => $nextYear,
            'next_year_exists' => $nextYearExists,
            // Pre-apertura di N+1 necessaria solo se c'è cross-year e l'anno
            // N+1 non esiste ancora (RB8/RB10).
            'next_year_needs_preopen' => $crossYearDeadlines !== [] && ! $nextYearExists,
        ];
    }

    /**
     * Riga spesa annuale = copia indipendente dei default del template (RB5).
     *
     * @return array<string, mixed>
     */
    public static function expenseRowFromItem(ExpenseItem $item): array
    {
        return [
            'expense_item_id' => $item->id,
            'name' => $item->name,
            'calculation_type' => $item->calculation_type->value,
            'is_pension_contribution' => $item->is_pension_contribution,
            'rate' => $item->default_rate !== null ? (float) $item->default_rate : null,
            'minimum' => $item->default_minimum !== null ? (float) $item->default_minimum : null,
            'maximum' => $item->default_maximum !== null ? (float) $item->default_maximum : null,
            'amount' => $item->default_amount !== null ? (float) $item->default_amount : null,
            'previous_year_credit' => null,
        ];
    }

    /**
     * Data prevista della scadenza: giorno/mese del template sull'anno
     * calendariale corretto (N se due_year_offset=current, N+1 se next). Il
     * giorno viene troncato all'ultimo del mese per gestire i template "31"
     * su mesi corti (es. 31/02 → 28/29 febbraio).
     */
    private static function dueDate(int $year, RecurringDeadline $rd): Carbon
    {
        $targetYear = $rd->due_year_offset->value === 'next' ? $year + 1 : $year;
        $lastDay = (int) Carbon::create($targetYear, $rd->month, 1)->endOfMonth()->day;

        return Carbon::create($targetYear, $rd->month, min($rd->day, $lastDay))->startOfDay();
    }
}
