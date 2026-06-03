<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\MarkDeadlineNotDue;
use App\Actions\Studiofinance\RegisterPayment;
use App\Actions\Studiofinance\ReopenDeadline;
use App\Concerns\FlashesToast;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Http\Requests\RegisterPaymentRequest;
use App\Models\Deadline;
use App\Services\DeadlineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scadenze: vista cronologica pluriennale con filtri (stato, tipo, anno) e
 * importo previsto per riga, più la registrazione del pagamento dal side-sheet.
 * Thin controller: query/mapping in [[DeadlineService]], transazione in
 * [[App\Actions\Studiofinance\RegisterPayment]]. Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class DeadlineController extends Controller
{
    use FlashesToast;

    public function __construct(private readonly DeadlineService $deadlines) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        // Toggle stato: open (da fare) | closed (completate + non dovute).
        $state = in_array($request->query('state'), ['open', 'closed'], true)
            ? (string) $request->query('state')
            : null;
        $kind = $this->stringOrNull($request->query('kind'));
        $year = $this->intOrNull($request->query('year'));

        return Inertia::render('deadlines/Index', [
            'deadlines' => $this->deadlines->paginate([
                'search' => $search,
                'state' => $state,
                'kind' => $kind,
                'year' => $year,
            ]),
            'filters' => [
                'search' => $search,
                'state' => $state,
                'kind' => $kind,
                'year' => $year,
            ],
            'availableYears' => $this->deadlines->availableYears(),
            'kindOptions' => $this->deadlines->kindOptions(),
        ]);
    }

    /**
     * Registra il pagamento di una scadenza (F7): planned→paid, open→completed.
     */
    public function registerPayment(RegisterPaymentRequest $request, Deadline $deadline, RegisterPayment $registerPayment): RedirectResponse
    {
        $registerPayment($deadline, $request->validated());

        $this->flashSuccess('Pagamento registrato.');

        return back();
    }

    /**
     * Riporta la scadenza ad aperta (F9), da completata o non dovuta.
     */
    public function reopen(Deadline $deadline, ReopenDeadline $reopen): RedirectResponse
    {
        abort_unless(
            in_array($deadline->status, [DeadlineStatus::Completed, DeadlineStatus::NotDue], true),
            422,
        );

        $reopen($deadline);

        $this->flashSuccess('Scadenza riaperta.');

        return back();
    }

    /**
     * Marca una scadenza di pagamento aperta come non dovuta (F9).
     */
    public function markNotDue(Deadline $deadline, MarkDeadlineNotDue $markNotDue): RedirectResponse
    {
        abort_unless($deadline->kind === DeadlineKind::Payment, 422);
        abort_unless($deadline->status === DeadlineStatus::Open, 422);

        $markNotDue($deadline);

        $this->flashSuccess('Scadenza segnata come non dovuta.');

        return back();
    }

    private function intOrNull(mixed $raw): ?int
    {
        $value = is_string($raw) ? trim($raw) : $raw;

        return is_numeric($value) ? (int) $value : null;
    }

    private function stringOrNull(mixed $raw): ?string
    {
        $value = is_string($raw) ? trim($raw) : '';

        return $value === '' ? null : $value;
    }
}
