<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\Deadline;
use Illuminate\Support\Facades\DB;

/**
 * Marca una scadenza di pagamento come `non dovuta` (F9): il pagamento
 * collegato diventa anch'esso `not_due`. In transazione.
 */
class MarkDeadlineNotDue
{
    public function __invoke(Deadline $deadline): void
    {
        DB::transaction(function () use ($deadline): void {
            $deadline->payment?->update(['status' => PaymentStatus::NotDue]);

            $deadline->update(['status' => DeadlineStatus::NotDue]);
        });
    }
}
