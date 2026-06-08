<?php

namespace Database\Seeders;

use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Enums\ExpenseYearOffset;
use App\Enums\QuotaType;
use App\Models\ExpenseItem;
use App\Models\RecurringDeadline;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed dei template iniziali (expense items + recurring deadlines) per un
 * nuovo utente forfettario architetto/Inarcassa. Eseguito dentro
 * CompleteOnboarding, NON da DatabaseSeeder globale (i template sono
 * per-utente, mai globali).
 *
 * Valori basati sul calendario fiscale 2025: ragionevoli ma sempre
 * modificabili dall'utente nelle Impostazioni prima di aprire un anno.
 *
 * Usage:
 *   (new StudiofinanceTemplatesSeeder())->seedForUser($user);
 */
class StudiofinanceTemplatesSeeder extends Seeder
{
    /**
     * @return array{expense_items: int, recurring_deadlines: int}
     */
    public function seedForUser(User $user): array
    {
        $this->seedFamilies($user);
        $items = $this->seedExpenseItems($user);
        $deadlines = $this->seedRecurringDeadlines($user, $items);

        return [
            'expense_items' => count($items),
            'recurring_deadlines' => count($deadlines),
        ];
    }

    /**
     * Le quattro famiglie (un tipo ciascuna), col nome di default rinominabile.
     */
    private function seedFamilies(User $user): void
    {
        foreach (ExpenseKind::cases() as $kind) {
            $user->expenseFamilies()->create([
                'kind' => $kind,
                'name' => $kind->label(),
            ]);
        }
    }

    /**
     * Default no-op: il seeder è per-utente, non globale.
     */
    public function run(): void
    {
        // intentionally empty.
    }

    /**
     * @return array<string, ExpenseItem> chiave = slug per linking deadlines.
     */
    private function seedExpenseItems(User $user): array
    {
        $rows = [
            'imposta_sostitutiva' => [
                'name' => 'Imposta sostitutiva',
                'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
                'kind' => ExpenseKind::Tax,
                'default_rate' => 15.00,
                'position' => 10,
            ],
            'inarcassa_soggettivo' => [
                'name' => 'Inarcassa Soggettivo',
                'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
                'kind' => ExpenseKind::Pension,
                'default_rate' => 14.50,
                'default_minimum' => 2435.00,
                'default_maximum' => 137195.00,
                'position' => 20,
            ],
            'inarcassa_integrativo' => [
                'name' => 'Inarcassa Integrativo',
                'calculation_type' => ExpenseCalculationType::PercentageOfIvaRevenue,
                'kind' => ExpenseKind::Pension,
                'default_rate' => 4.00,
                'default_minimum' => 815.00,
                'position' => 30,
            ],
            'inarcassa_maternita' => [
                'name' => 'Inarcassa Maternità',
                'calculation_type' => ExpenseCalculationType::FixedAnnual,
                'kind' => ExpenseKind::Pension,
                'default_amount' => 72.00,
                'position' => 40,
            ],
            'bolli' => [
                'name' => 'Bolli',
                'calculation_type' => ExpenseCalculationType::SumOfBolli,
                'kind' => ExpenseKind::StampDuty,
                'position' => 50,
            ],
            'commercialista' => [
                'name' => 'Commercialista',
                'calculation_type' => ExpenseCalculationType::FixedAnnual,
                'kind' => ExpenseKind::Fixed,
                'default_amount' => 300.00,
                'position' => 60,
            ],
            'assicurazione' => [
                'name' => 'Assicurazione professionale',
                'calculation_type' => ExpenseCalculationType::FixedAnnual,
                'kind' => ExpenseKind::Fixed,
                'default_amount' => 350.00,
                'position' => 70,
            ],
            'oato' => [
                'name' => 'Quota Ordine (OATO)',
                'calculation_type' => ExpenseCalculationType::FixedAnnual,
                'kind' => ExpenseKind::Fixed,
                'default_amount' => 230.00,
                'position' => 80,
            ],
        ];

        $created = [];
        foreach ($rows as $slug => $attrs) {
            $created[$slug] = $user->expenseItems()->create($attrs);
        }

        return $created;
    }

    /**
     * @param  array<string, ExpenseItem>  $items
     * @return array<int, RecurringDeadline>
     */
    private function seedRecurringDeadlines(User $user, array $items): array
    {
        // Legenda flag locali:
        //   due_next     = la data scadenza cade in N+1 (saldi, bolli Q4)
        //   expense_next = la scadenza paga la spesa di N+1 (solo commercialista)
        //   quota        = tipo quota → come si calcola l'importo previsto (RB8).
        //                  Le rate bolli sono full_amount ma l'importo è gestito
        //                  a parte (saldo maturato), vedi DeadlineExpectation.
        // Default (assenti) = N / N.
        $rows = [
            // Imposta sostitutiva — acconti in N, saldo in N+1 (sempre spesa N).
            [
                'name' => '1° acconto imposta sostitutiva',
                'day' => 30, 'month' => 6,
                'kind' => DeadlineKind::Payment,
                'item' => 'imposta_sostitutiva',
                'quota' => QuotaType::TaxAdvance,
            ],
            [
                'name' => '2° acconto imposta sostitutiva',
                'day' => 30, 'month' => 11,
                'kind' => DeadlineKind::Payment,
                'item' => 'imposta_sostitutiva',
                'quota' => QuotaType::TaxAdvance,
            ],
            [
                'name' => 'Saldo imposta sostitutiva',
                'day' => 30, 'month' => 6,
                'kind' => DeadlineKind::Payment,
                'item' => 'imposta_sostitutiva',
                'due_next' => true,
                'quota' => QuotaType::TaxBalance,
            ],

            // Inarcassa Soggettivo — 2 rate acconto in N, saldo in N+1.
            [
                'name' => '1ª rata Inarcassa Soggettivo',
                'day' => 30, 'month' => 6,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_soggettivo',
                'quota' => QuotaType::ContributionMinimum,
            ],
            [
                'name' => '2ª rata Inarcassa Soggettivo',
                'day' => 30, 'month' => 9,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_soggettivo',
                'quota' => QuotaType::ContributionMinimum,
            ],
            [
                'name' => 'Saldo Inarcassa Soggettivo',
                'day' => 31, 'month' => 12,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_soggettivo',
                'due_next' => true,
                'quota' => QuotaType::ContributionAdjustment,
            ],

            // Inarcassa Integrativo — 2 rate acconto in N, saldo in N+1.
            [
                'name' => '1ª rata Inarcassa Integrativo',
                'day' => 30, 'month' => 6,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_integrativo',
                'quota' => QuotaType::ContributionMinimum,
            ],
            [
                'name' => '2ª rata Inarcassa Integrativo',
                'day' => 30, 'month' => 9,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_integrativo',
                'quota' => QuotaType::ContributionMinimum,
            ],
            [
                'name' => 'Saldo Inarcassa Integrativo',
                'day' => 31, 'month' => 12,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_integrativo',
                'due_next' => true,
                'quota' => QuotaType::ContributionAdjustment,
            ],

            // Inarcassa Maternità — 2 rate acconto in N.
            [
                'name' => '1ª rata Inarcassa Maternità',
                'day' => 30, 'month' => 6,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_maternita',
                // Fissa, nessun minimale: full_amount ÷ n° rate → quota/2.
                'quota' => QuotaType::FullAmount,
            ],
            [
                'name' => '2ª rata Inarcassa Maternità',
                'day' => 30, 'month' => 9,
                'kind' => DeadlineKind::Payment,
                'item' => 'inarcassa_maternita',
                'quota' => QuotaType::FullAmount,
            ],

            // Bolli — Q1, Q2, Q3 in N; Q4 paga in N+1 ma spesa di N.
            [
                'name' => 'Bolli — 1° trimestre',
                'day' => 31, 'month' => 5,
                'kind' => DeadlineKind::Payment,
                'item' => 'bolli',
                'quota' => QuotaType::FullAmount,
            ],
            [
                'name' => 'Bolli — 2° trimestre',
                'day' => 30, 'month' => 9,
                'kind' => DeadlineKind::Payment,
                'item' => 'bolli',
                'quota' => QuotaType::FullAmount,
            ],
            [
                'name' => 'Bolli — 3° trimestre',
                'day' => 30, 'month' => 11,
                'kind' => DeadlineKind::Payment,
                'item' => 'bolli',
                'quota' => QuotaType::FullAmount,
            ],
            [
                'name' => 'Bolli — 4° trimestre',
                'day' => 28, 'month' => 2,
                'kind' => DeadlineKind::Payment,
                'item' => 'bolli',
                'due_next' => true,
                'quota' => QuotaType::FullAmount,
            ],

            // Assicurazione + OATO — rinnovi a marzo (data N, spesa N).
            [
                'name' => 'Assicurazione professionale',
                'day' => 31, 'month' => 3,
                'kind' => DeadlineKind::Payment,
                'item' => 'assicurazione',
                'quota' => QuotaType::FullAmount,
            ],
            [
                'name' => 'Quota Ordine (OATO)',
                'day' => 31, 'month' => 3,
                'kind' => DeadlineKind::Payment,
                'item' => 'oato',
                'quota' => QuotaType::FullAmount,
            ],

            // Commercialista — UNICO caso forward: data 31/12/N, spesa N+1.
            // Trigger pre-aperto N+1 se non esiste al wizard.
            [
                'name' => 'Commercialista (parcella anno successivo)',
                'day' => 31, 'month' => 12,
                'kind' => DeadlineKind::Payment,
                'item' => 'commercialista',
                'expense_next' => true,
                'quota' => QuotaType::FullAmount,
            ],

            // Adempimenti — nessun pagamento collegato.
            // Dichiarazione redditi (Modello Redditi PF): dal 2024 scadenza
            // ordinaria 30/11 dell'anno successivo al periodo d'imposta.
            [
                'name' => 'Dichiarazione redditi',
                'day' => 31, 'month' => 5,
                'kind' => DeadlineKind::Fulfillment,
                'item' => null,
                'due_next' => true,
            ],
            [
                'name' => 'Comunicazione reddituale Inarcassa',
                'day' => 31, 'month' => 10,
                'kind' => DeadlineKind::Fulfillment,
                'item' => null,
                'due_next' => true,
            ],
        ];

        $created = [];
        foreach ($rows as $row) {
            $created[] = $user->recurringDeadlines()->create([
                'name' => $row['name'],
                'day' => $row['day'],
                'month' => $row['month'],
                'kind' => $row['kind'],
                'expense_item_id' => $row['item'] !== null ? $items[$row['item']]->id : null,
                'due_year_offset' => ($row['due_next'] ?? false)
                    ? DueYearOffset::Next
                    : DueYearOffset::Current,
                'expense_year_offset' => ($row['expense_next'] ?? false)
                    ? ExpenseYearOffset::Next
                    : ExpenseYearOffset::Current,
                'quota_type' => $row['quota'] ?? null,
            ]);
        }

        return $created;
    }
}
