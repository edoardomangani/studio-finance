<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateExpenseFamilyRequest;
use App\Models\ExpenseFamily;
use App\Services\ExpenseFamilyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Famiglie di spesa (Impostazioni): i quattro tipi fissi, solo rinominabili.
 * Niente create/destroy. Thin controller; logica in [[ExpenseFamilyService]].
 */
class ExpenseFamilyController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly ExpenseFamilyService $families) {}

    public function index(): Response
    {
        return Inertia::render('settings/Families/Index', [
            'families' => $this->families->list(),
        ]);
    }

    public function update(UpdateExpenseFamilyRequest $request, ExpenseFamily $expenseFamily): RedirectResponse
    {
        $this->families->rename($expenseFamily, $request->validated()['name']);

        $this->flashSuccess('Famiglia rinominata.');

        return to_route('settings.families.index');
    }
}
