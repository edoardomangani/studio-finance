<?php

namespace App\Actions\Studiofinance;

use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Payment;

/**
 * Registra un pagamento manuale extra-scadenza (F8): cassa reale non legata a
 * una scadenza generata dal wizard. Nasce già `paid` (concorre ai totali della
 * spesa, RB9) con `deadline_id` null. La spesa è validata e tenancy-safe a
 * monte da [[App\Http\Requests\RegisterManualPaymentRequest]].
 */
class RegisterManualPayment
{
    /**
     * @param  array{description?: ?string, amount: mixed, paid_at: mixed}  $data
     */
    public function __invoke(AnnualExpense $annualExpense, array $data): Payment
    {
        // user_id è forzato dal trait BelongsToUser in creating (= Auth::id()).
        return Payment::create([
            'annual_expense_id' => $annualExpense->id,
            'deadline_id' => null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'],
            'status' => PaymentStatus::Paid,
        ]);
    }
}
