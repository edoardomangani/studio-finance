<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRecurringDeadlineRequest;
use App\Http\Requests\Settings\UpdateRecurringDeadlineRequest;
use App\Models\RecurringDeadline;
use App\Services\RecurringDeadlineService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD scadenze tipo template (catalogo).
 * UI in settings/RecurringDeadlines/Index.vue (tabella + dialog inline).
 *
 * Logica in [[RecurringDeadlineService]]; controller thin.
 */
class RecurringDeadlineController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly RecurringDeadlineService $deadlines) {}

    public function index(): Response
    {
        return Inertia::render('settings/RecurringDeadlines/Index', [
            'recurringDeadlines' => $this->deadlines->list(),
            'kinds' => $this->deadlines->kindOptions(),
            'dueYearOffsets' => $this->deadlines->dueYearOffsetOptions(),
            'expenseYearOffsets' => $this->deadlines->expenseYearOffsetOptions(),
            'activeExpenseItems' => $this->deadlines->activeExpenseItems(),
        ]);
    }

    public function store(StoreRecurringDeadlineRequest $request): RedirectResponse
    {
        $this->deadlines->create($request->validated());

        $this->flashSuccess('Scadenza tipo creata.');

        return to_route('settings.recurring-deadlines.index');
    }

    public function update(UpdateRecurringDeadlineRequest $request, RecurringDeadline $recurringDeadline): RedirectResponse
    {
        $this->deadlines->update($recurringDeadline, $request->validated());

        $this->flashSuccess('Scadenza tipo aggiornata.');

        return to_route('settings.recurring-deadlines.index');
    }

    public function destroy(RecurringDeadline $recurringDeadline): RedirectResponse
    {
        $this->deadlines->archive($recurringDeadline);

        $this->flashSuccess('Scadenza tipo archiviata.');

        return to_route('settings.recurring-deadlines.index');
    }
}
