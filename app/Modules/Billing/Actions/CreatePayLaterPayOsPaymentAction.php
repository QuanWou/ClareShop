<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\PayLaterPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePayLaterPayOsPaymentAction
{
    public function __construct(private readonly InitializeBillingPayOsPaymentAction $initializePayOs) {}

    public function execute(User $user, PayLaterPurchase $purchase): BillingPaymentAttempt
    {
        $attempt = DB::transaction(function () use ($user, $purchase): BillingPaymentAttempt {
            $locked = PayLaterPurchase::query()->whereKey($purchase->getKey())->where('user_id', $user->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->canPayNow()) {
                throw ValidationException::withMessages(['payment_method' => 'Khoản trả sau hiện chưa thể thanh toán.']);
            }

            $locked->attempts()->where('status', 'pending')->update(['status' => 'expired', 'expires_at' => now()]);
            $locked->update(['status' => 'processing', 'selected_payment_method' => 'payos']);

            return BillingPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'pay_later_purchase_id' => $locked->getKey(),
                'purpose' => 'pay_later',
                'provider' => 'payos',
                'amount' => $locked->amount_due,
                'currency' => 'VND',
                'status' => 'pending',
            ]);
        });

        return $this->initializePayOs->execute($attempt);
    }
}
