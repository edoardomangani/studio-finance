<?php

namespace App\Services;

use App\Models\Deadline;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Dati pre-caricati di cui [[DeadlineExpectation]] ha bisogno per calcolare in
 * modo puro (senza query) l'importo previsto di un insieme di scadenze.
 * Assemblato dall'orchestratore tramite [[DeadlineContextBuilder]].
 */
final class DeadlineContext
{
    /**
     * @param  array<int, YearAmounts>  $amountsByYear  chiave = numero anno (anno della spesa + anno N-1 per gli acconti)
     * @param  Collection<int, Payment>  $paidPayments  pagamenti pagati sulle spese referenziate, con `deadline` caricata
     * @param  Collection<int, Deadline>  $deadlines  scadenze in scope, per contare le rate fratelle
     */
    public function __construct(
        public readonly array $amountsByYear,
        public readonly Collection $paidPayments,
        public readonly Collection $deadlines,
    ) {}
}
