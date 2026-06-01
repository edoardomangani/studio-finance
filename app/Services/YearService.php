<?php

namespace App\Services;

use App\Models\User;
use App\Models\Year;
use Illuminate\Support\Carbon;

/**
 * Service per il dominio Year.
 *
 * Owner di: lista pluriennale mappata per la Index, anno suggerito di
 * default per il wizard, mapping `forShow` (placeholder Fase 6, esteso in
 * Fase 9 con i KPI fiscali). La logica transazionale di apertura vive
 * nell'action [[App\Actions\Studiofinance\OpenYear]]; il "piano" editabile
 * in [[YearOpeningPlanner]].
 */
class YearService
{
    public function __construct(private readonly YearOpeningPlanner $planner) {}

    /**
     * Anni dell'utente in ordine DESC, con i conteggi di spese e scadenze.
     * Niente paginazione: i volumi attesi sono pochi anni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return Year::query()
            ->withCount(['annualExpenses', 'deadlines'])
            ->orderByDesc('year')
            ->get()
            ->map(fn (Year $year): array => [
                'id' => $year->id,
                'year' => $year->year,
                'profitability_coefficient' => (float) $year->profitability_coefficient,
                'pre_opened' => $year->pre_opened,
                'expenses_count' => $year->annual_expenses_count,
                'deadlines_count' => $year->deadlines_count,
            ])
            ->all();
    }

    /**
     * Piano editabile di apertura per un anno (spese da template + scadenze
     * con date calcolate + rilevazione cross-year).
     *
     * @return array<string, mixed>
     */
    public function plan(User $user, int $year): array
    {
        return $this->planner->plan($user, $year);
    }

    /**
     * Anno (modello) per numero, 404 se non esiste per l'utente. Tenancy via
     * global scope [[App\Concerns\BelongsToUser]].
     */
    public function findByYear(int $year): Year
    {
        return Year::query()->where('year', $year)->firstOrFail();
    }

    /**
     * Anno da proporre di default nel wizard: l'anno corrente se non ancora
     * aperto, altrimenti il primo anno successivo libero (F6 step 1).
     */
    public function suggestedYear(): int
    {
        $current = (int) Carbon::now()->year;

        $openYears = Year::query()->pluck('year')->all();

        $candidate = $current;
        while (in_array($candidate, $openYears, true)) {
            $candidate++;
        }

        return $candidate;
    }

    /**
     * Mapping per la vista anno. Placeholder di Fase 6: KPI fiscali a 0,
     * popolati in Fase 9 (CalcoliAnno). Espone già spese e conteggi per non
     * lasciare la pagina vuota dopo l'apertura.
     *
     * @return array<string, mixed>
     */
    public function forShow(Year $year): array
    {
        $year->loadMissing(['annualExpenses' => fn ($q) => $q->orderBy('id')]);
        $year->loadCount('deadlines');

        return [
            'id' => $year->id,
            'year' => $year->year,
            'profitability_coefficient' => (float) $year->profitability_coefficient,
            'pre_opened' => $year->pre_opened,
            'note' => $year->note,
            'deadlines_count' => $year->deadlines_count,
            'expenses' => $year->annualExpenses
                ->map(fn ($e): array => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'calculation_type' => $e->calculation_type->value,
                    'calculation_type_label' => $e->calculation_type->label(),
                    'rate' => $e->rate !== null ? (float) $e->rate : null,
                    'minimum' => $e->minimum !== null ? (float) $e->minimum : null,
                    'maximum' => $e->maximum !== null ? (float) $e->maximum : null,
                    'amount' => $e->amount !== null ? (float) $e->amount : null,
                ])
                ->all(),
        ];
    }
}
