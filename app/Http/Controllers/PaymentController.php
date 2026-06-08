<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\RegisterManualPayment;
use App\Concerns\FlashesToast;
use App\Concerns\NormalizesFacetFilters;
use App\Http\Requests\RegisterManualPaymentRequest;
use App\Http\Requests\UpdateManualPaymentRequest;
use App\Models\AnnualExpense;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pagamenti: vista pluriennale (RB9) con filtri (stato, anno spesa, anno data
 * effettiva) e registrazione di un pagamento manuale extra-scadenza (F8).
 * Thin controller: query/mapping in [[PaymentService]], persistenza in
 * [[App\Actions\Studiofinance\RegisterManualPayment]]. Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class PaymentController extends Controller
{
    use FlashesToast, NormalizesFacetFilters;

    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        // Faccette multi-select (array). Accetta anche lo scalare per comodità.
        $expenseYear = $this->intArray($request->query('expense_year'));
        $paidYear = $this->intArray($request->query('paid_year'));

        return Inertia::render('payments/Index', [
            'payments' => $this->payments->paginate([
                'search' => $search,
                'expense_year' => $expenseYear,
                'paid_year' => $paidYear,
            ]),
            'filters' => [
                'search' => $search,
                'expense_year' => $expenseYear,
                'paid_year' => $paidYear,
            ],
            'availableExpenseYears' => $this->payments->availableExpenseYears(),
            'availablePaidYears' => $this->payments->availablePaidYears(),
            'annualExpenses' => $this->payments->annualExpensesForPicker(),
        ]);
    }

    /**
     * Registra un pagamento manuale extra-scadenza (F8). La spesa è già
     * validata e tenancy-safe dal form request; il findOrFail ri-applica il
     * global scope come seconda barriera.
     */
    public function store(RegisterManualPaymentRequest $request, RegisterManualPayment $register): RedirectResponse
    {
        $data = $request->validated();
        $annualExpense = AnnualExpense::findOrFail($data['annual_expense_id']);

        $register($annualExpense, $data);

        $this->flashSuccess('Pagamento registrato.');

        return back();
    }

    /**
     * Modifica un pagamento manuale. I pagamenti da scadenza si gestiscono
     * dalla scadenza (ne possiede il ciclo di vita): qui sono off-limits.
     */
    public function update(UpdateManualPaymentRequest $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->deadline_id === null, 403);

        $this->payments->update($payment, $request->validated());

        $this->flashSuccess('Pagamento aggiornato.');

        return back();
    }

    /**
     * Elimina (soft delete) un pagamento manuale. Stesso guard dell'update.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        abort_unless($payment->deadline_id === null, 403);

        $this->payments->delete($payment);

        $this->flashSuccess('Pagamento eliminato.');

        return back();
    }
}
