<?php

namespace App\Actions\Studiofinance;

use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\Deadline;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Registra il pagamento di una scadenza (F7): il pagamento pianificato 1:1
 * passa a `paid` con importo e data, la scadenza passa a `completed`. In una
 * transazione: i due stati non devono mai divergere.
 *
 * Presuppone una scadenza di pagamento aperta (validato da
 * [[App\Http\Requests\RegisterPaymentRequest]]).
 */
class RegisterPayment
{
    /**
     * @param  array{description?: ?string, amount: mixed, paid_at: mixed}  $data
     */
    public function __invoke(Deadline $deadline, array $data): void
    {
        DB::transaction(function () use ($deadline, $data): void {
            // La scadenza di pagamento ha un pagamento pianificato 1:1 dal
            // wizard; lo creiamo solo come fallback difensivo.
            $payment = $deadline->payment ?? new Payment([
                'user_id' => $deadline->user_id,
                'annual_expense_id' => $deadline->annual_expense_id,
                'deadline_id' => $deadline->id,
            ]);

            $payment->fill([
                'description' => $data['description'] ?? $deadline->name,
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'status' => PaymentStatus::Paid,
            ])->save();

            $deadline->update(['status' => DeadlineStatus::Completed]);
        });
    }
}
