<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Enums\ExpenseCalculationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreExpenseItemRequest;
use App\Http\Requests\Settings\UpdateExpenseItemRequest;
use App\Models\ExpenseItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD voci di spesa template (catalogo).
 * UI in settings/ExpenseItems/Index.vue (tabella + dialog inline).
 *
 * Tenancy via [[App\Concerns\BelongsToUser]]: route-model binding fa 404 se
 * l'expense item non appartiene all'utente.
 *
 * Soft delete: "archivia" nasconde dal catalogo. Le istanze già create
 * negli anni esistenti restano referenziate via FK e non vengono toccate.
 */
class ExpenseItemController extends Controller
{
    use FlashesToast;

    public function index(): Response
    {
        return Inertia::render('settings/ExpenseItems/Index', [
            'expenseItems' => $this->mapExpenseItems(),
            'calculationTypes' => $this->calculationTypeOptions(),
        ]);
    }

    public function store(StoreExpenseItemRequest $request): RedirectResponse
    {
        ExpenseItem::create($request->validated());

        $this->flashSuccess('Voce di spesa creata.');

        return to_route('settings.expense-items.index');
    }

    public function update(UpdateExpenseItemRequest $request, ExpenseItem $expenseItem): RedirectResponse
    {
        $expenseItem->update($request->validated());

        $this->flashSuccess('Voce di spesa aggiornata.');

        return to_route('settings.expense-items.index');
    }

    public function destroy(ExpenseItem $expenseItem): RedirectResponse
    {
        $expenseItem->delete();

        $this->flashSuccess('Voce di spesa archiviata.');

        return to_route('settings.expense-items.index');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapExpenseItems(): array
    {
        return ExpenseItem::query()
            ->select([
                'id',
                'name',
                'calculation_type',
                'default_rate',
                'default_minimum',
                'default_maximum',
                'default_amount',
                'active',
                'position',
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (ExpenseItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'calculation_type' => $item->calculation_type->value,
                'calculation_type_label' => $item->calculation_type->label(),
                // decimal:2 Eloquent restituisce string: castiamo a float
                // in modo che il frontend riceva tipi consistenti `number | null`
                // e non debba fare Number(value) sparso nei componenti.
                'default_rate' => $item->default_rate !== null ? (float) $item->default_rate : null,
                'default_minimum' => $item->default_minimum !== null ? (float) $item->default_minimum : null,
                'default_maximum' => $item->default_maximum !== null ? (float) $item->default_maximum : null,
                'default_amount' => $item->default_amount !== null ? (float) $item->default_amount : null,
                'active' => $item->active,
                'position' => $item->position,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function calculationTypeOptions(): array
    {
        return array_map(
            fn (ExpenseCalculationType $t) => ['value' => $t->value, 'label' => $t->label()],
            ExpenseCalculationType::cases(),
        );
    }
}
