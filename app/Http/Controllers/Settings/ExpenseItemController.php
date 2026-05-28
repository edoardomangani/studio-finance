<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreExpenseItemRequest;
use App\Http\Requests\Settings\UpdateExpenseItemRequest;
use App\Models\ExpenseItem;
use App\Services\ExpenseItemService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD voci di spesa template (catalogo).
 * UI in settings/ExpenseItems/Index.vue (tabella + dialog inline).
 *
 * Logica (query, mapping, persistence) in [[ExpenseItemService]];
 * controller thin.
 */
class ExpenseItemController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly ExpenseItemService $items) {}

    public function index(): Response
    {
        return Inertia::render('settings/ExpenseItems/Index', [
            'expenseItems' => $this->items->list(),
            'calculationTypes' => $this->items->calculationTypeOptions(),
        ]);
    }

    public function store(StoreExpenseItemRequest $request): RedirectResponse
    {
        $this->items->create($request->validated());

        $this->flashSuccess('Voce di spesa creata.');

        return to_route('settings.expense-items.index');
    }

    public function update(UpdateExpenseItemRequest $request, ExpenseItem $expenseItem): RedirectResponse
    {
        $this->items->update($expenseItem, $request->validated());

        $this->flashSuccess('Voce di spesa aggiornata.');

        return to_route('settings.expense-items.index');
    }

    public function destroy(ExpenseItem $expenseItem): RedirectResponse
    {
        $this->items->archive($expenseItem);

        $this->flashSuccess('Voce di spesa archiviata.');

        return to_route('settings.expense-items.index');
    }
}
