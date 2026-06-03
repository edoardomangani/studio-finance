<?php

namespace App\Services;

use App\Enums\DeadlineKind;
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

        if ($deadline->quota_type === null) {
            return null;
        }

        return match ($deadline->quota_type) {
            QuotaType::TaxAdvance => $this->taxAdvance($deadline, $expense, $context),
            QuotaType::TaxBalance => $this->balance($expense, QuotaType::TaxAdvance, $context),
            QuotaType::ContributionMinimum => $this->splitFloor($deadline, $expense, $context),
            QuotaType::ContributionAdjustment => $this->balance($expense, QuotaType::ContributionMinimum, $context),
            QuotaType::FullAmount => $this->fullAmount($deadline, $expense, $context),
        };
    }

    /**
     * Acconto imposta: IS netta dell'anno N-1 ÷ n° acconti. null se l'anno
     * precedente (o la sua spesa IS) non è nel context.
     */
    private function taxAdvance(Deadline $deadline, AnnualExpense $expense, DeadlineContext $context): ?float
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

        $net = (float) $previous->expenseAmounts[$previousIs->id]['definitive'];

        return round($net / $this->siblingCount($deadline, $context), 2);
    }

    /**
     * Saldo/conguaglio: definitive della spesa − quanto già pagato sulle rate
     * della quota indicata (acconti o minimi).
     */
    private function balance(AnnualExpense $expense, QuotaType $paidQuota, DeadlineContext $context): ?float
    {
        $definitive = $this->definitive($expense, $context);

        if ($definitive === null) {
            return null;
        }

        return round($definitive - $this->paidForQuota($expense, $paidQuota, $context), 2);
    }

    /**
     * Rata del minimale contributivo: minimale della voce ÷ n° rate. null se la
     * voce non ha minimale.
     */
    private function splitFloor(Deadline $deadline, AnnualExpense $expense, DeadlineContext $context): ?float
    {
        if ($expense->minimum === null) {
            return null;
        }

        return round((float) $expense->minimum / $this->siblingCount($deadline, $context), 2);
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

        $issuedUpTo = $amounts->invoices
            ->filter(fn ($invoice): bool => $invoice->issued_at->lessThanOrEqualTo($deadline->due_at));

        $accrued = $this->revenueCalculator->stampDuty($issuedUpTo);

        return round($accrued - $this->paidOnExpense($expense, $context), 2);
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
