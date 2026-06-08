<?php

namespace App\Services;

use App\Models\ExpenseFamily;

/**
 * Service per le famiglie di spesa: i quattro tipi fissi, di cui l'utente può
 * solo modificare il nome. Tenancy via [[App\Concerns\BelongsToUser]].
 */
class ExpenseFamilyService
{
    /**
     * @return list<array{id: int, kind: string, name: string, description: string}>
     */
    public function list(): array
    {
        return ExpenseFamily::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ExpenseFamily $family): array => [
                'id' => $family->id,
                'kind' => $family->kind->value,
                'name' => $family->name,
                'description' => $family->kind->description(),
            ])
            ->all();
    }

    public function rename(ExpenseFamily $family, string $name): void
    {
        $family->update(['name' => $name]);
    }
}
