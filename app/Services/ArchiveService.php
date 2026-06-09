<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ExpenseItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringDeadline;

/**
 * Vista archivio (F11): elenca i record soft-deletati delle entità archiviabili
 * e li ripristina. Ogni lista è mappata a una riga uniforme
 * (`{id, name, detail, amount, archived_at}`) così la UI usa una sola tabella.
 * Tenancy: `onlyTrashed()` resta sotto il global scope BelongsToUser, quindi
 * sono sempre e solo i cestinati dell'utente loggato.
 */
class ArchiveService
{
    /** Tipo (segmento URL) → modello. Whitelist per il restore sicuro. */
    private const MODELS = [
        'clients' => Client::class,
        'invoices' => Invoice::class,
        'expense-items' => ExpenseItem::class,
        'recurring-deadlines' => RecurringDeadline::class,
        'payments' => Payment::class,
    ];

    /**
     * Tipi archiviabili (segmenti URL ammessi): vincola la rotta di restore, così
     * un tipo fuori whitelist è 404 a livello di routing e il service riceve
     * sempre un tipo valido.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::MODELS);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function forIndex(): array
    {
        return [
            'clients' => $this->clients(),
            'invoices' => $this->invoices(),
            'expense-items' => $this->expenseItems(),
            'recurring-deadlines' => $this->recurringDeadlines(),
            'payments' => $this->payments(),
        ];
    }

    /**
     * Ripristina un record cestinato. Il `type` è già nella whitelist (vincolo
     * di rotta). `onlyTrashed()->findOrFail` è scoped all'utente: un id estraneo
     * (o non cestinato) dà 404, mai un restore altrui.
     */
    public function restore(string $type, int $id): void
    {
        $model = self::MODELS[$type];

        $model::onlyTrashed()->findOrFail($id)->restore();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clients(): array
    {
        return Client::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (Client $c): array => $this->row(
                $c->id,
                $c->name,
                $c->vat_number ?? $c->tax_code,
                null,
                $c->deleted_at?->toDateString(),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invoices(): array
    {
        return Invoice::onlyTrashed()
            ->with(['client' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (Invoice $i): array => $this->row(
                $i->id,
                "Fattura {$i->number}",
                $i->client?->name,
                (float) $i->total,
                $i->deleted_at?->toDateString(),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expenseItems(): array
    {
        return ExpenseItem::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (ExpenseItem $e): array => $this->row(
                $e->id,
                $e->name,
                null,
                null,
                $e->deleted_at?->toDateString(),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recurringDeadlines(): array
    {
        return RecurringDeadline::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (RecurringDeadline $r): array => $this->row(
                $r->id,
                $r->name,
                null,
                null,
                $r->deleted_at?->toDateString(),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function payments(): array
    {
        return Payment::onlyTrashed()
            ->with(['annualExpense' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (Payment $p): array => $this->row(
                $p->id,
                $p->description ?? $p->annualExpense?->name ?? 'Pagamento',
                $p->paid_at?->toDateString(),
                (float) $p->amount,
                $p->deleted_at?->toDateString(),
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $id, string $name, ?string $detail, ?float $amount, ?string $archivedAt): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'detail' => $detail,
            'amount' => $amount,
            'archived_at' => $archivedAt,
        ];
    }
}
