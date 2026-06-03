<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\Deadline;
use Illuminate\Support\Facades\DB;

/**
 * Riporta una scadenza ad `aperta` (F9), da `completata` (annulla completamento)
 * o da `non dovuta` (riapri): la transizione è identica. Il pagamento collegato
 * torna `planned` con importo e data azzerati. In transazione: stato scadenza e
 * pagamento non devono divergere.
 */
class ReopenDeadline
{
    public function __invoke(Deadline $deadline): void
    {
        DB::transaction(function () use ($deadline): void {
            $deadline->payment?->update([
                'status' => PaymentStatus::Planned,
                'amount' => null,
                'paid_at' => null,
            ]);

            $deadline->update(['status' => DeadlineStatus::Open]);
        });
    }
}
