<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Year;
use Illuminate\Support\Facades\DB;

/**
 * Crea una scadenza ad-hoc (non da template): l'escape hatch per un obbligo
 * non previsto dalle scadenze tipo. Stessa forma di ciò che genera il wizard
 * di apertura anno ([[OpenYear]]), così la registrazione del pagamento e la
 * reversibilità seguono il percorso identico:
 *
 * - kind = payment → scadenza legata alla spesa annuale scelta (che fissa
 *   anche l'anno) + pagamento pianificato 1:1. `quota_type` resta null: niente
 *   previsto calcolato, l'utente digita l'importo alla registrazione.
 * - kind = fulfillment → solo scadenza nell'anno scelto, nessun pagamento.
 *
 * Modelli risolti via findOrFail (global scope [[App\Concerns\BelongsToUser]]
 * = tenancy); il form request li ha già validati come appartenenti all'utente.
 */
class CreateDeadline
{
    /**
     * @param  array{kind: string, name: string, due_at: mixed, annual_expense_id?: int|null, year_id?: int|null, manual_expected_amount?: mixed}  $data
     */
    public function __invoke(array $data): Deadline
    {
        return DB::transaction(function () use ($data): Deadline {
            $kind = DeadlineKind::from($data['kind']);

            if ($kind === DeadlineKind::Fulfillment) {
                $year = Year::findOrFail($data['year_id']);

                return $year->deadlines()->create([
                    'name' => $data['name'],
                    'due_at' => $data['due_at'],
                    'kind' => DeadlineKind::Fulfillment,
                    'status' => DeadlineStatus::Open,
                ]);
            }

            $expense = AnnualExpense::findOrFail($data['annual_expense_id']);

            $deadline = $expense->year->deadlines()->create([
                'name' => $data['name'],
                'due_at' => $data['due_at'],
                'kind' => DeadlineKind::Payment,
                'annual_expense_id' => $expense->id,
                'manual_expected_amount' => $data['manual_expected_amount'] ?? null,
                'status' => DeadlineStatus::Open,
            ]);

            // Pagamento pianificato 1:1 (importo e data vuoti finché non registrato).
            $deadline->payment()->create([
                'annual_expense_id' => $expense->id,
                'description' => $data['name'],
                'status' => PaymentStatus::Planned,
            ]);

            return $deadline;
        });
    }
}
