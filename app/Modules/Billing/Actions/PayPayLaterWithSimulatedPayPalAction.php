<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\PayLaterPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayPayLaterWithSimulatedPayPalAction
{
    public function __construct(private readonly CompletePayLaterPurchaseAction $complete) {}

    public function execute(User $user, PayLaterPurchase $purchase): PayLaterPurchase
    {
        return DB::transaction(function () use ($user, $purchase): PayLaterPurchase {
            $locked = PayLaterPurchase::query()->whereKey($purchase->getKey())->where('user_id', $user->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->canPayNow()) {
                throw ValidationException::withMessages(['payment_method' => 'Khoản trả sau hiện chưa thể thanh toán.']);
            }

            BillingPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'pay_later_purchase_id' => $locked->getKey(),
                'purpose' => 'pay_later',
                'provider' => 'paypal_simulated',
                'amount' => $locked->amount_due,
                'currency' => 'VND',
                'status' => 'paid',
                'paid_at' => now(),
                'payload' => ['simulation' => true, 'confirmed_by_user' => true],
            ]);
            $locked->update(['status' => 'processing', 'selected_payment_method' => 'paypal']);

            return $this->complete->execute($locked, 'paypal');
        });
    }
}
