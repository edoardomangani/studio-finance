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
     * @param  array<string, int>  $siblingCounts  rate totali per (annual_expense_id:quota_type) — conteggio reale, non limitato alla pagina
     * @param  array<string, int>  $openSiblingCounts  rate ancora APERTE per (annual_expense_id:quota_type): servono al saldo per stimare gli acconti da pagare ma non ancora versati
     * @param  array<int, Collection<int, Deadline>>  $siblingDeadlines  scadenze di pagamento (id, due_at, status) per annual_expense_id: servono alla catena bolli per attribuire a ogni rata solo il suo periodo
     */
    public function __construct(
        public readonly array $amountsByYear,
        public readonly Collection $paidPayments,
        public readonly array $siblingCounts,
        public readonly array $openSiblingCounts,
        public readonly array $siblingDeadlines,
    ) {}
}
