<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Enums\ExpenseKind;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreAnnualExpenseRequest;
use App\Http\Requests\UpdateAnnualExpenseRequest;
use App\Models\AnnualExpense;
use App\Models\Year;
use App\Services\AnnualExpenseService;
use Illuminate\Http\RedirectResponse;

/**
 * Modifica dei valori di una spesa annuale dalla vista anno (correzione di
 * errori di generazione). Thin controller: validazione per tipo in
 * [[UpdateAnnualExpenseRequest]], persistenza in [[AnnualExpenseService]].
 * Tenancy via route-model binding ([[App\Concerns\BelongsToUser]]: 404 se la
 * spesa non appartiene all'utente).
 */
class AnnualExpenseController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly AnnualExpenseService $expenses) {}

    /**
     * Crea una spesa una-tantum per l'anno (importo fisso). L'anno è validato e
     * tenancy-safe dal form request; il findOrFail ri-applica il global scope.
     */
    public function store(StoreAnnualExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $year = Year::findOrFail($data['year_id']);

        $this->expenses->create($year, $data['name'], (float) $data['amount'], ExpenseKind::from($data['kind']));

        $this->flashSuccess('Spesa aggiunta.');

        return back();
    }

    public function update(UpdateAnnualExpenseRequest $request, AnnualExpense $annualExpense): RedirectResponse
    {
        $this->expenses->update($annualExpense, $request->validated());

        $this->flashSuccess('Spesa aggiornata.');

        return back();
    }

    /**
     * Elimina una spesa una-tantum. Le voci da template (con `expense_item_id`)
     * non si cancellano post-apertura; nemmeno quelle con pagamenti registrati
     * (cassa orfana) o scadenze collegate (scadenze orfane).
     */
    public function destroy(AnnualExpense $annualExpense): RedirectResponse
    {
        abort_unless($annualExpense->expense_item_id === null, 403);
        abort_if(
            $annualExpense->payments()->where('status', PaymentStatus::Paid)->exists(),
            403,
        );
        abort_if($annualExpense->deadlines()->exists(), 403);

        $this->expenses->delete($annualExpense);

        $this->flashSuccess('Spesa eliminata.');

        return back();
    }
}
