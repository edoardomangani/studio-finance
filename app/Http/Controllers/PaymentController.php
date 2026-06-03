<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\RegisterManualPayment;
use App\Concerns\FlashesToast;
use App\Concerns\NormalizesFacetFilters;
use App\Http\Requests\RegisterManualPaymentRequest;
use App\Models\AnnualExpense;
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
        $status = $this->stringArray($request->query('status'));
        $expenseYear = $this->intArray($request->query('expense_year'));
        $paidYear = $this->intArray($request->query('paid_year'));

        return Inertia::render('payments/Index', [
            'payments' => $this->payments->paginate([
                'search' => $search,
                'status' => $status,
                'expense_year' => $expenseYear,
                'paid_year' => $paidYear,
            ]),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'expense_year' => $expenseYear,
                'paid_year' => $paidYear,
            ],
            'availableExpenseYears' => $this->payments->availableExpenseYears(),
            'availablePaidYears' => $this->payments->availablePaidYears(),
            'statusOptions' => $this->payments->statusOptions(),
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
}
