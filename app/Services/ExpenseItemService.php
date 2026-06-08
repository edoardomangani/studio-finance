<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\ExpenseFamily;
use App\Models\ExpenseItem;

/**
 * Service per ExpenseItem (catalogo template voci di spesa).
 *
 * Tenancy via [[App\Concerns\BelongsToUser]]. Niente paginazione: il
 * catalogo template è per definizione corto (poche decine di voci).
 */
class ExpenseItemService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $familyNames = ExpenseFamily::namesByKind();

        return ExpenseItem::query()
            ->select([
                'id',
                'name',
                'calculation_type',
                'kind',
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
            ->map(fn (ExpenseItem $item) => $this->toListItem($item, $familyNames))
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function calculationTypeOptions(): array
    {
        return array_map(
            fn (ExpenseCalculationType $t) => ['value' => $t->value, 'label' => $t->label()],
            ExpenseCalculationType::cases(),
        );
    }

    /**
     * Opzioni famiglia per il form template: i 4 tipi, etichetta = nome
     * (rinominabile) della famiglia dell'utente.
     *
     * @return list<array{value: string, label: string}>
     */
    public function kindOptions(): array
    {
        $familyNames = ExpenseFamily::namesByKind();

        return array_map(
            fn (ExpenseKind $k) => ['value' => $k->value, 'label' => $familyNames[$k->value] ?? $k->label()],
            ExpenseKind::cases(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExpenseItem
    {
        return ExpenseItem::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseItem $item, array $data): ExpenseItem
    {
        $item->update($data);

        return $item;
    }

    public function archive(ExpenseItem $item): void
    {
        $item->delete();
    }

    /**
     * @param  array<string, string>  $familyNames  mappa kind => nome famiglia
     * @return array<string, mixed>
     */
    private function toListItem(ExpenseItem $item, array $familyNames): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'calculation_type' => $item->calculation_type->value,
            'calculation_type_label' => $item->calculation_type->label(),
            'kind' => $item->kind->value,
            'family_name' => $familyNames[$item->kind->value] ?? $item->kind->label(),
            // decimal:2 Eloquent restituisce string: castiamo a float in modo
            // che il frontend riceva tipi consistenti `number | null`.
            'default_rate' => $item->default_rate !== null ? (float) $item->default_rate : null,
            'default_minimum' => $item->default_minimum !== null ? (float) $item->default_minimum : null,
            'default_maximum' => $item->default_maximum !== null ? (float) $item->default_maximum : null,
            'default_amount' => $item->default_amount !== null ? (float) $item->default_amount : null,
            'active' => $item->active,
            'position' => $item->position,
        ];
    }
}
