<?php

namespace Database\Seeders;

use App\Enums\AnnoDataScadenza;
use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoCalcoloVoceSpesa;
use App\Enums\TipoScadenza;
use App\Models\ScadenzaTipo;
use App\Models\User;
use App\Models\VoceSpesa;
use Illuminate\Database\Seeder;

/**
 * Seed dei template iniziali (voci di spesa + scadenze tipo) per un nuovo
 * utente forfettario architetto/Inarcassa. Eseguito dentro CompleteOnboarding,
 * NON da DatabaseSeeder globale (i template sono per-utente, mai globali).
 *
 * Valori di default basati sul calendario fiscale 2025. Sono ragionevoli ma
 * sempre modificabili dall'utente nelle Impostazioni prima di aprire l'anno.
 *
 * Usage:
 *   (new StudiofinanceTemplatesSeeder())->seedForUser($user);
 */
class StudiofinanceTemplatesSeeder extends Seeder
{
    /**
     * @return array{voci: int, scadenze: int}
     */
    public function seedForUser(User $user): array
    {
        $voci = $this->seedVociSpesa($user);
        $scadenze = $this->seedScadenzeTipo($user, $voci);

        return [
            'voci' => count($voci),
            'scadenze' => count($scadenze),
        ];
    }

    /**
     * Default no-op: il seeder è per-utente, non globale.
     */
    public function run(): void
    {
        // intentionally empty.
    }

    /**
     * @return array<string, VoceSpesa> chiave = slug per linking scadenze.
     */
    private function seedVociSpesa(User $user): array
    {
        $rows = [
            'imposta_sostitutiva' => [
                'nome' => 'Imposta sostitutiva',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::PercRedditoIrpef,
                'aliquota_default' => 15.00,
                'ordine' => 10,
            ],
            'inarcassa_soggettivo' => [
                'nome' => 'Inarcassa Soggettivo',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::PercRedditoIrpef,
                'aliquota_default' => 14.50,
                'minimale_default' => 2435.00,
                'massimale_default' => 137195.00,
                'ordine' => 20,
            ],
            'inarcassa_integrativo' => [
                'nome' => 'Inarcassa Integrativo',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::PercVolumeAffariIva,
                'aliquota_default' => 4.00,
                'minimale_default' => 815.00,
                'ordine' => 30,
            ],
            'inarcassa_maternita' => [
                'nome' => 'Inarcassa Maternità',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale,
                'quota_default' => 72.00,
                'ordine' => 40,
            ],
            'bolli' => [
                'nome' => 'Bolli',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::SommaBolli,
                'ordine' => 50,
            ],
            'commercialista' => [
                'nome' => 'Commercialista',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale,
                'quota_default' => 300.00,
                'ordine' => 60,
            ],
            'assicurazione' => [
                'nome' => 'Assicurazione professionale',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale,
                'quota_default' => 350.00,
                'ordine' => 70,
            ],
            'oato' => [
                'nome' => 'Quota Ordine (OATO)',
                'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale,
                'quota_default' => 230.00,
                'ordine' => 80,
            ],
        ];

        $created = [];
        foreach ($rows as $slug => $attrs) {
            $created[$slug] = $user->vociSpesa()->create($attrs);
        }

        return $created;
    }

    /**
     * @param  array<string, VoceSpesa>  $voci
     * @return array<int, ScadenzaTipo>
     */
    private function seedScadenzeTipo(User $user, array $voci): array
    {
        // Convenzione legenda:
        //   data_successivo = la data scadenza cade in N+1 (saldi, bolli Q4)
        //   spesa_successivo = la scadenza paga la spesa di N+1 (solo commercialista)
        // Default (assenti) = N / N.
        $rows = [
            // Imposta sostitutiva — acconti in N, saldo in N+1 (sempre spesa N).
            [
                'nome' => '1° acconto imposta sostitutiva',
                'giorno' => 30, 'mese' => 6,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'imposta_sostitutiva',
            ],
            [
                'nome' => '2° acconto imposta sostitutiva',
                'giorno' => 30, 'mese' => 11,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'imposta_sostitutiva',
            ],
            [
                'nome' => 'Saldo imposta sostitutiva',
                'giorno' => 30, 'mese' => 6,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'imposta_sostitutiva',
                'data_successivo' => true,
            ],

            // Inarcassa Soggettivo — 2 rate acconto in N, saldo in N+1.
            [
                'nome' => '1ª rata Inarcassa Soggettivo',
                'giorno' => 30, 'mese' => 6,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_soggettivo',
            ],
            [
                'nome' => '2ª rata Inarcassa Soggettivo',
                'giorno' => 30, 'mese' => 9,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_soggettivo',
            ],
            [
                'nome' => 'Saldo Inarcassa Soggettivo',
                'giorno' => 31, 'mese' => 12,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_soggettivo',
                'data_successivo' => true,
            ],

            // Inarcassa Integrativo — 2 rate acconto in N, saldo in N+1.
            [
                'nome' => '1ª rata Inarcassa Integrativo',
                'giorno' => 30, 'mese' => 6,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_integrativo',
            ],
            [
                'nome' => '2ª rata Inarcassa Integrativo',
                'giorno' => 30, 'mese' => 9,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_integrativo',
            ],
            [
                'nome' => 'Saldo Inarcassa Integrativo',
                'giorno' => 31, 'mese' => 12,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_integrativo',
                'data_successivo' => true,
            ],

            // Inarcassa Maternità — quota unica annuale, cassa in N.
            [
                'nome' => 'Inarcassa Maternità',
                'giorno' => 30, 'mese' => 9,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'inarcassa_maternita',
            ],

            // Bolli — Q1, Q2, Q3 in N; Q4 paga in N+1 ma spesa di N.
            [
                'nome' => 'Bolli — 1° trimestre',
                'giorno' => 31, 'mese' => 5,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'bolli',
            ],
            [
                'nome' => 'Bolli — 2° trimestre',
                'giorno' => 30, 'mese' => 9,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'bolli',
            ],
            [
                'nome' => 'Bolli — 3° trimestre',
                'giorno' => 30, 'mese' => 11,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'bolli',
            ],
            [
                'nome' => 'Bolli — 4° trimestre',
                'giorno' => 28, 'mese' => 2,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'bolli',
                'data_successivo' => true,
            ],

            // Assicurazione + OATO — rinnovi a marzo (data N, spesa N).
            [
                'nome' => 'Assicurazione professionale',
                'giorno' => 31, 'mese' => 3,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'assicurazione',
            ],
            [
                'nome' => 'Quota Ordine (OATO)',
                'giorno' => 31, 'mese' => 3,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'oato',
            ],

            // Commercialista — UNICO caso forward: data 31/12/N, spesa anno N+1.
            // Trigger pre-aperto N+1 se non esiste al wizard.
            [
                'nome' => 'Commercialista (parcella anno successivo)',
                'giorno' => 31, 'mese' => 12,
                'tipo' => TipoScadenza::Pagamento,
                'voce' => 'commercialista',
                'spesa_successivo' => true,
            ],

            // Adempimenti — nessun pagamento collegato.
            [
                'nome' => 'Dichiarazione redditi',
                'giorno' => 31, 'mese' => 10,
                'tipo' => TipoScadenza::Adempimento,
                'voce' => null,
                'data_successivo' => true,
            ],
            [
                'nome' => 'Comunicazione reddituale Inarcassa (Dich.RED)',
                'giorno' => 31, 'mese' => 10,
                'tipo' => TipoScadenza::Adempimento,
                'voce' => null,
                'data_successivo' => true,
            ],
        ];

        $created = [];
        foreach ($rows as $row) {
            $created[] = $user->scadenzeTipo()->create([
                'nome' => $row['nome'],
                'giorno' => $row['giorno'],
                'mese' => $row['mese'],
                'tipo' => $row['tipo'],
                'voce_spesa_id' => $row['voce'] !== null ? $voci[$row['voce']]->id : null,
                'anno_data_scadenza' => ($row['data_successivo'] ?? false)
                    ? AnnoDataScadenza::Successivo
                    : AnnoDataScadenza::Corrente,
                'anno_riferimento_spesa' => ($row['spesa_successivo'] ?? false)
                    ? AnnoRiferimentoSpesa::Successivo
                    : AnnoRiferimentoSpesa::Corrente,
            ]);
        }

        return $created;
    }
}
