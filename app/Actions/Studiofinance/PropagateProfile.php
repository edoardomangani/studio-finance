<?php

namespace App\Actions\Studiofinance;

use App\Models\AnnualExpense;
use App\Models\ProfessionalProfile;
use App\Models\Year;
use Illuminate\Support\Facades\DB;

/**
 * Propaga le modifiche del profilo professionale agli anni selezionati (F11).
 * Coefficiente → `Year.profitability_coefficient`. Anno di inizio attività →
 * aliquota della voce "Imposta sostitutiva" dell'anno (5/15% secondo la finestra
 * start-up, vedi [[ProfessionalProfile::impostaSostitutivaRateFor]]). I template
 * di spesa non vengono toccati: la propagazione vive solo sugli anni esistenti.
 */
class PropagateProfile
{
    /**
     * @param  array<int, int>  $yearIds  anni scelti nella checklist del dialog
     */
    public function handle(ProfessionalProfile $profile, array $yearIds, bool $coefficient, bool $startYear): void
    {
        if (! $coefficient && ! $startYear) {
            return;
        }

        // Scoped via BelongsToUser: id estranei all'utente vengono esclusi.
        // Le spese servono solo per l'aliquota IS (propagazione anno-inizio).
        $years = Year::query()
            ->whereIn('id', $yearIds)
            ->when($startYear, fn ($q) => $q->with('annualExpenses'))
            ->get();

        DB::transaction(function () use ($profile, $years, $coefficient, $startYear): void {
            foreach ($years as $year) {
                if ($coefficient) {
                    $year->update(['profitability_coefficient' => $profile->profitability_coefficient]);
                }

                if ($startYear) {
                    $rate = $profile->impostaSostitutivaRateFor($year->year);
                    $year->annualExpenses
                        ->filter(fn (AnnualExpense $expense): bool => $expense->isImpostaSostitutiva())
                        ->each(fn (AnnualExpense $expense) => $expense->update(['rate' => $rate]));
                }
            }
        });
    }
}
