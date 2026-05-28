<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Enums\AnnoDataScadenza;
use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoScadenza;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreScadenzaTipoRequest;
use App\Http\Requests\Settings\UpdateScadenzaTipoRequest;
use App\Models\ScadenzaTipo;
use App\Models\VoceSpesa;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD scadenze tipo template. UI in settings/ScadenzeTipo/Index.vue
 * (tabella + dialog inline).
 */
class ScadenzeTipoController extends Controller
{
    use FlashesToast;

    public function index(): Response
    {
        return Inertia::render('settings/ScadenzeTipo/Index', [
            'scadenzeTipo' => $this->mapScadenzeTipo(),
            'tipiScadenza' => $this->tipoScadenzaOptions(),
            'anniData' => $this->annoDataOptions(),
            'anniRiferimento' => $this->annoRiferimentoOptions(),
            'vociAttive' => $this->vociAttive(),
        ]);
    }

    public function store(StoreScadenzaTipoRequest $request): RedirectResponse
    {
        ScadenzaTipo::create($request->validated());

        $this->flashSuccess('Scadenza tipo creata.');

        return to_route('settings.scadenze-tipo.index');
    }

    public function update(UpdateScadenzaTipoRequest $request, ScadenzaTipo $scadenzaTipo): RedirectResponse
    {
        $scadenzaTipo->update($request->validated());

        $this->flashSuccess('Scadenza tipo aggiornata.');

        return to_route('settings.scadenze-tipo.index');
    }

    public function destroy(ScadenzaTipo $scadenzaTipo): RedirectResponse
    {
        $scadenzaTipo->delete();

        $this->flashSuccess('Scadenza tipo archiviata.');

        return to_route('settings.scadenze-tipo.index');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapScadenzeTipo(): array
    {
        return ScadenzaTipo::query()
            ->with('voceSpesa:id,nome')
            ->orderBy('mese')
            ->orderBy('giorno')
            ->orderBy('id')
            ->get()
            ->map(fn (ScadenzaTipo $scadenza) => [
                'id' => $scadenza->id,
                'nome' => $scadenza->nome,
                'giorno' => $scadenza->giorno,
                'mese' => $scadenza->mese,
                'tipo' => $scadenza->tipo->value,
                'tipo_label' => $scadenza->tipo->label(),
                'voce_spesa_id' => $scadenza->voce_spesa_id,
                'voce_spesa_nome' => $scadenza->voceSpesa?->nome,
                'anno_data_scadenza' => $scadenza->anno_data_scadenza->value,
                'anno_data_label' => $scadenza->anno_data_scadenza->label(),
                'anno_riferimento_spesa' => $scadenza->anno_riferimento_spesa->value,
                'anno_riferimento_label' => $scadenza->anno_riferimento_spesa->label(),
                'attiva' => $scadenza->attiva,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function tipoScadenzaOptions(): array
    {
        return array_map(
            fn (TipoScadenza $t) => ['value' => $t->value, 'label' => $t->label()],
            TipoScadenza::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function annoDataOptions(): array
    {
        return array_map(
            fn (AnnoDataScadenza $a) => ['value' => $a->value, 'label' => $a->label()],
            AnnoDataScadenza::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function annoRiferimentoOptions(): array
    {
        return array_map(
            fn (AnnoRiferimentoSpesa $a) => ['value' => $a->value, 'label' => $a->label()],
            AnnoRiferimentoSpesa::cases(),
        );
    }

    /**
     * @return list<array{id: int, nome: string}>
     */
    private function vociAttive(): array
    {
        return VoceSpesa::query()
            ->where('attiva', true)
            ->orderBy('ordine')
            ->get(['id', 'nome'])
            ->map(fn (VoceSpesa $v) => ['id' => $v->id, 'nome' => $v->nome])
            ->values()
            ->all();
    }
}
