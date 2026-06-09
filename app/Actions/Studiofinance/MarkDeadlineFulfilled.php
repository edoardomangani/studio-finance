<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;

/**
 * Marca un adempimento come `svolto`: open→completed. Nessun pagamento
 * collegato (kind=fulfillment), quindi nessuna transazione: un solo update.
 */
class MarkDeadlineFulfilled
{
    public function __invoke(Deadline $deadline): void
    {
        $deadline->update(['status' => DeadlineStatus::Completed]);
    }
}
