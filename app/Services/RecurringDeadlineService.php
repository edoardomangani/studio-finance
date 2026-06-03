<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use App\Enums\QuotaType;
use App\Models\ExpenseItem;
use App\Models\RecurringDeadline;

/**
 * Service per RecurringDeadline (catalogo template scadenze tipo).
 *
 * Tenancy via [[App\Concerns\BelongsToUser]]. Niente paginazione: catalogo
 * corto (~20 righe). Eager-loading di expenseItem per evitare N+1 al
 * mapping.
 */
class RecurringDeadlineService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return RecurringDeadline::query()
            ->select([
                'id',
                'name',
                'day',
                'month',
                'kind',
                'expense_item_id',
                'due_year_offset',
                'expense_year_offset',
                'quota_type',
                'active',
            ])
            ->with('expenseItem:id,name')
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('id')
            ->get()
            ->map(fn (RecurringDeadline $d) => $this->toListItem($d))
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function kindOptions(): array
    {
        return array_map(
            fn (DeadlineKind $k) => ['value' => $k->value, 'label' => $k->label()],
            DeadlineKind::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function dueYearOffsetOptions(): array
    {
        return array_map(
            fn (DueYearOffset $o) => ['value' => $o->value, 'label' => $o->label()],
            DueYearOffset::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function expenseYearOffsetOptions(): array
    {
        return array_map(
            fn (ExpenseYearOffset $o) => ['value' => $o->value, 'label' => $o->label()],
            ExpenseYearOffset::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function quotaTypeOptions(): array
    {
        return array_map(
            fn (QuotaType $q) => ['value' => $q->value, 'label' => $q->label()],
            QuotaType::cases(),
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function activeExpenseItems(): array
    {
        return ExpenseItem::query()
            ->where('active', true)
            ->orderBy('position')
            ->get(['id', 'name'])
            ->map(fn (ExpenseItem $i) => ['id' => $i->id, 'name' => $i->name])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RecurringDeadline
    {
        return RecurringDeadline::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RecurringDeadline $deadline, array $data): RecurringDeadline
    {
        $deadline->update($data);

        return $deadline;
    }

    public function archive(RecurringDeadline $deadline): void
    {
        $deadline->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(RecurringDeadline $d): array
    {
        return [
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
            'quota_type' => $d->quota_type?->value,
            'quota_type_label' => $d->quota_type?->label(),
            'active' => $d->active,
        ];
    }
}
