<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\ExpenseCalculationType;
use App\Enums\QuotaType;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Payment;

/**
 * Importo previsto (suggerimento) di una scadenza di pagamento — vista derivata,
 * mai a DB (RB8). Calcolatore PURO: riceve un [[DeadlineContext]] con i dati già
 * caricati (importi d'anno, pagamenti pagati, scadenze fratelle) e non interroga
 * il DB. Lo switch è sul `quota_type`, con un ramo dedicato per i bolli (saldo
 * maturato). Restituisce `null` quando i dati necessari non esistono (es. anno
 * precedente assente al primo anno): suggerimento assente.
 *
 * È un suggerimento precompilato ed editabile: al pagamento l'utente conferma o
 * corregge la cifra reale (da F24 / avviso).
 */
class DeadlineExpectation
{
    public function __construct(
        private readonly RevenueCalculator $revenueCalculator,
    ) {}

    /**
     * Importo previsto della scadenza, o null se non calcolabile / non applicabile.
     */
    public function for(Deadline $deadline, DeadlineContext $context): ?float
    {
        if ($deadline->kind !== DeadlineKind::Payment) {
            return null;
        }

        $expense = $deadline->annualExpense;

        if ($expense === null) {
            return null;
        }

        // Bolli: il dovuto a una scadenza = bolli emessi fino alla sua data −
        // bolli già pagati. Saldo che cresce nel tempo; se salti una rata, la
        // successiva mostra l'arretrato. Indipendente dal quota_type.
        if ($expense->calculation_type === ExpenseCalculationType::SumOfBolli) {
            return $this->stampDutyBalance($deadline, $expense, $context);
        }

        // Niente quota_type → scadenza ad-hoc: nessun calcolo derivato (il
        // quota_type, e il suo split tra fratelle, vive solo sulle standard).
        // Il previsto è quello fissato a mano dall'utente, se presente.
        if ($deadline->quota_type === null) {
            return $deadline->manual_expected_amount !== null
                ? (float) $deadline->manual_expected_amount
                : null;
        }

        return match ($deadline->quota_type) {
            QuotaType::TaxAdvance => $this->advanceExpected($expense, QuotaType::TaxAdvance, $context),
            QuotaType::TaxBalance => $this->balance($expense, QuotaType::TaxAdvance, $context),
            QuotaType::ContributionMinimum => $this->advanceExpected($expense, QuotaType::ContributionMinimum, $context),
            QuotaType::ContributionAdjustment => $this->balance($expense, QuotaType::ContributionMinimum, $context),
            QuotaType::FullAmount => $this->fullAmount($deadline, $expense, $context),
        };
    }

    /**
     * Previsto di una rata di acconto:
     *  - acconto imposta (TaxAdvance): IS netta dell'anno N-1 ÷ n° acconti;
     *  - rata del minimale contributivo (ContributionMinimum): minimale ÷ n° rate.
     * null se i dati non esistono (anno precedente assente, voce senza minimale).
     */
    private function advanceExpected(AnnualExpense $expense, QuotaType $quota, DeadlineContext $context): ?float
    {
        $count = $this->siblingCountByKey($expense, $quota, $context);

        if ($quota === QuotaType::TaxAdvance) {
            $net = $this->previousImpostaSostitutivaNet($expense, $context);

            return $net === null ? null : round($net / $count, 2);
        }

        if ($quota === QuotaType::ContributionMinimum) {
            return $expense->minimum === null ? null : round((float) $expense->minimum / $count, 2);
        }

        return null;
    }

    /**
     * IS netta (definitive) dell'anno N-1 della spesa; null se l'anno precedente
     * o la sua voce di imposta sostitutiva non sono nel context.
     */
    private function previousImpostaSostitutivaNet(AnnualExpense $expense, DeadlineContext $context): ?float
    {
        $previous = $context->amountsByYear[$expense->year->year - 1] ?? null;

        if ($previous === null) {
            return null;
        }

        $previousIs = $previous->expenses
            ->first(fn (AnnualExpense $e): bool => YearExpenseAmounts::isImpostaSostitutiva($e));

        if ($previousIs === null) {
            return null;
        }

        return (float) $previous->expenseAmounts[$previousIs->id]['definitive'];
    }

    /**
     * Saldo/conguaglio: definitive della spesa − quanto è coperto dalle rate di
     * acconto/minimo. Una rata coperta vale: il versato reale se pagata, il suo
     * previsto se ancora aperta (verrà pagata), zero se "non dovuta" (in quel
     * caso l'importo resta nel saldo). Così il saldo non mostra più il totale
     * pieno finché gli acconti sono solo aperti.
     */
    private function balance(AnnualExpense $expense, QuotaType $advanceQuota, DeadlineContext $context): ?float
    {
        $definitive = $this->definitive($expense, $context);

        if ($definitive === null) {
            return null;
        }

        // Rate già pagate → importo realmente versato.
        $covered = $this->paidForQuota($expense, $advanceQuota, $context);

        // Rate ancora aperte → si stima il loro previsto (saranno versate). Le
        // non dovute non rientrano: non sono né pagate né aperte.
        $openCount = $context->openSiblingCounts[$expense->id.':'.$advanceQuota->value] ?? 0;
        $perAdvance = $this->advanceExpected($expense, $advanceQuota, $context);

        if ($perAdvance !== null) {
            $covered = round($covered + $openCount * $perAdvance, 2);
        }

        return round($definitive - $covered, 2);
    }

    /**
     * Quota intera: definitive della spesa ÷ n° rate (= definitive intero per i
     * pagamenti unici, dove c'è una sola scadenza di questa quota).
     */
    private function fullAmount(Deadline $deadline, AnnualExpense $expense, DeadlineContext $context): ?float
    {
        $definitive = $this->definitive($expense, $context);

        if ($definitive === null) {
            return null;
        }

        return round($definitive / $this->siblingCount($deadline, $context), 2);
    }

    /**
     * Saldo bolli alla data della scadenza: bolli sulle fatture dell'anno della
     * spesa emesse entro `due_at`, meno i bolli già pagati su quella spesa.
     */
    private function stampDutyBalance(Deadline $deadline, AnnualExpense $expense, DeadlineContext $context): ?float
    {
        $amounts = $context->amountsByYear[$expense->year->year] ?? null;

        if ($amounts === null) {
            return null;
        }

        $accruedUpTo = fn ($dueAt): float => $this->revenueCalculator->stampDuty(
            $amounts->invoices->filter(fn ($invoice): bool => $invoice->issued_at->lessThanOrEqualTo($dueAt)),
        );

        $siblings = ($context->siblingDeadlines[$expense->id] ?? collect())
            ->sortBy('due_at')
            ->values();

        // Catena delle rate bolli: ogni rata copre i bolli del SUO periodo. Le
        // precedenti pagate riducono col versato reale, le aperte col loro
        // previsto (saranno pagate), le non dovute con zero (arretrato che
        // scivola sul saldo successivo). Così una rata aperta non mostra il
        // cumulato delle precedenti, ma solo il proprio trimestre.
        $covered = 0.0;
        foreach ($siblings as $sibling) {
            $expected = round($accruedUpTo($sibling->due_at) - $covered, 2);

            if ($sibling->id === $deadline->id) {
                return $expected;
            }

            $coverage = match ($sibling->status) {
                DeadlineStatus::Completed => $this->paidOnDeadline($sibling->id, $context),
                DeadlineStatus::Open => $expected,
                default => 0.0,
            };
            $covered = round($covered + $coverage, 2);
        }

        // Fallback (scadenza non tra i fratelli noti): saldo classico sull'intera spesa.
        return round($accruedUpTo($deadline->due_at) - $this->paidOnExpense($expense, $context), 2);
    }

    /**
     * Somma pagata su una specifica scadenza.
     */
    private function paidOnDeadline(int $deadlineId, DeadlineContext $context): float
    {
        return round((float) $context->paidPayments
            ->where('deadline_id', $deadlineId)
            ->sum('amount'), 2);
    }

    /**
     * Definitive della spesa dall'anno caricato nel context; null se assente.
     */
    private function definitive(AnnualExpense $expense, DeadlineContext $context): ?float
    {
        $amounts = $context->amountsByYear[$expense->year->year] ?? null;

        return $amounts !== null && isset($amounts->expenseAmounts[$expense->id])
            ? (float) $amounts->expenseAmounts[$expense->id]['definitive']
            : null;
    }

    /**
     * Numero di scadenze fratelle con lo stesso quota_type sulla stessa spesa:
     * il denominatore dello split (rate). Almeno 1.
     */
    private function siblingCount(Deadline $deadline, DeadlineContext $context): int
    {
        $key = $deadline->annual_expense_id.':'.$deadline->quota_type?->value;

        return max(1, $context->siblingCounts[$key] ?? 1);
    }

    /**
     * Come [[siblingCount]] ma per (spesa, quota) espliciti: usato dal saldo per
     * il denominatore delle rate di acconto/minimo.
     */
    private function siblingCountByKey(AnnualExpense $expense, QuotaType $quota, DeadlineContext $context): int
    {
        return max(1, $context->siblingCounts[$expense->id.':'.$quota->value] ?? 1);
    }

    /**
     * Somma pagata sulle scadenze di una certa quota della spesa.
     */
    private function paidForQuota(AnnualExpense $expense, QuotaType $quota, DeadlineContext $context): float
    {
        return round((float) $context->paidPayments
            ->filter(fn (Payment $p): bool => $p->annual_expense_id === $expense->id
                && $p->deadline?->quota_type === $quota)
            ->sum('amount'), 2);
    }

    /**
     * Somma pagata sulla spesa, indipendentemente dalla scadenza.
     */
    private function paidOnExpense(AnnualExpense $expense, DeadlineContext $context): float
    {
        return round((float) $context->paidPayments
            ->where('annual_expense_id', $expense->id)
            ->sum('amount'), 2);
    }
}
