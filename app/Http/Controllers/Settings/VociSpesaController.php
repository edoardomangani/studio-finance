<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Enums\TipoCalcoloVoceSpesa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreVoceSpesaRequest;
use App\Http\Requests\Settings\UpdateVoceSpesaRequest;
use App\Models\VoceSpesa;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD voci di spesa template. La pagina UI vive in
 * settings/VociSpesa/Index.vue (tabella + dialog inline).
 *
 * Il global scope di BelongsToUser garantisce tenancy: route-model
 * binding 404 se la voce non appartiene all'utente.
 *
 * Soft delete: "archivia" la nasconde dal catalogo. Le istanze già create
 * negli anni esistenti restano referenziate via FK; non vengono toccate
 * dal soft delete del template.
 */
class VociSpesaController extends Controller
{
    use FlashesToast;

    public function index(): Response
    {
        return Inertia::render('settings/VociSpesa/Index', [
            'vociSpesa' => $this->mapVociSpesa(),
            'tipiCalcolo' => $this->tipoCalcoloOptions(),
        ]);
    }

    public function store(StoreVoceSpesaRequest $request): RedirectResponse
    {
        VoceSpesa::create($request->validated());

        $this->flashSuccess('Voce di spesa creata.');

        return to_route('settings.voci-spesa.index');
    }

    public function update(UpdateVoceSpesaRequest $request, VoceSpesa $voceSpesa): RedirectResponse
    {
        $voceSpesa->update($request->validated());

        $this->flashSuccess('Voce di spesa aggiornata.');

        return to_route('settings.voci-spesa.index');
    }

    public function destroy(VoceSpesa $voceSpesa): RedirectResponse
    {
        $voceSpesa->delete();

        $this->flashSuccess('Voce di spesa archiviata.');

        return to_route('settings.voci-spesa.index');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapVociSpesa(): array
    {
        return VoceSpesa::query()
            ->orderBy('ordine')
            ->orderBy('id')
            ->get()
            ->map(fn (VoceSpesa $voce) => [
                'id' => $voce->id,
                'nome' => $voce->nome,
                'tipo_calcolo' => $voce->tipo_calcolo->value,
                'tipo_calcolo_label' => $voce->tipo_calcolo->label(),
                'aliquota_default' => $voce->aliquota_default,
                'minimale_default' => $voce->minimale_default,
                'massimale_default' => $voce->massimale_default,
                'quota_default' => $voce->quota_default,
                'attiva' => $voce->attiva,
                'ordine' => $voce->ordine,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function tipoCalcoloOptions(): array
    {
        return array_map(
            fn (TipoCalcoloVoceSpesa $t) => ['value' => $t->value, 'label' => $t->label()],
            TipoCalcoloVoceSpesa::cases(),
        );
    }
}
