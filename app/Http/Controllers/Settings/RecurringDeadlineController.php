<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRecurringDeadlineRequest;
use App\Http\Requests\Settings\UpdateRecurringDeadlineRequest;
use App\Models\ExpenseItem;
use App\Models\RecurringDeadline;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD scadenze tipo template (catalogo).
 * UI in settings/RecurringDeadlines/Index.vue (tabella + dialog inline).
 */
class RecurringDeadlineController extends Controller
{
    use FlashesToast;

    public function index(): Response
    {
        return Inertia::render('settings/RecurringDeadlines/Index', [
            'recurringDeadlines' => $this->mapRecurringDeadlines(),
            'kinds' => $this->kindOptions(),
            'dueYearOffsets' => $this->dueYearOffsetOptions(),
            'expenseYearOffsets' => $this->expenseYearOffsetOptions(),
            'activeExpenseItems' => $this->activeExpenseItems(),
        ]);
    }

    public function store(StoreRecurringDeadlineRequest $request): RedirectResponse
    {
        RecurringDeadline::create($request->validated());

        $this->flashSuccess('Scadenza tipo creata.');

        return to_route('settings.recurring-deadlines.index');
    }

    public function update(UpdateRecurringDeadlineRequest $request, RecurringDeadline $recurringDeadline): RedirectResponse
    {
        $recurringDeadline->update($request->validated());

        $this->flashSuccess('Scadenza tipo aggiornata.');

        return to_route('settings.recurring-deadlines.index');
    }

    public function destroy(RecurringDeadline $recurringDeadline): RedirectResponse
    {
        $recurringDeadline->delete();

        $this->flashSuccess('Scadenza tipo archiviata.');

        return to_route('settings.recurring-deadlines.index');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapRecurringDeadlines(): array
    {
        return RecurringDeadline::query()
            ->with('expenseItem:id,name')
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('id')
            ->get()
            ->map(fn (RecurringDeadline $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'day' => $d->day,
                'month' => $d->month,
                'kind' => $d->kind->value,
                'kind_label' => $d->kind->label(),
                'expense_item_id' => $d->expense_item_id,
                'expense_item_name' => $d->expenseItem?->name,
                'due_year_offset' => $d->due_year_offset->value,
                'due_year_offset_label' => $d->due_year_offset->label(),
                'expense_year_offset' => $d->expense_year_offset->value,
                'expense_year_offset_label' => $d->expense_year_offset->label(),
                'active' => $d->active,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function kindOptions(): array
    {
        return array_map(
            fn (DeadlineKind $k) => ['value' => $k->value, 'label' => $k->label()],
            DeadlineKind::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function dueYearOffsetOptions(): array
    {
        return array_map(
            fn (DueYearOffset $o) => ['value' => $o->value, 'label' => $o->label()],
            DueYearOffset::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function expenseYearOffsetOptions(): array
    {
        return array_map(
            fn (ExpenseYearOffset $o) => ['value' => $o->value, 'label' => $o->label()],
            ExpenseYearOffset::cases(),
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function activeExpenseItems(): array
    {
        return ExpenseItem::query()
            ->where('active', true)
            ->orderBy('position')
            ->get(['id', 'name'])
            ->map(fn (ExpenseItem $i) => ['id' => $i->id, 'name' => $i->name])
            ->values()
            ->all();
    }
}
