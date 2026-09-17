<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\ClarePayTransaction;
use App\Modules\Billing\Models\ClarePayWallet;
use App\Modules\Billing\Models\PayLaterPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayPayLaterWithClarePayAction
{
    public function __construct(private readonly CompletePayLaterPurchaseAction $complete) {}

    public function execute(User $user, PayLaterPurchase $purchase): PayLaterPurchase
    {
        return DB::transaction(function () use ($user, $purchase): PayLaterPurchase {
            $lockedPurchase = PayLaterPurchase::query()->whereKey($purchase->getKey())->where('user_id', $user->getKey())->lockForUpdate()->firstOrFail();
            if (! $lockedPurchase->canPayNow()) {
                throw ValidationException::withMessages(['payment_method' => 'Khoản trả sau hiện chưa thể thanh toán.']);
            }

            $wallet = ClarePayWallet::query()->where('user_id', $user->getKey())->lockForUpdate()->first();
            $balance = (float) ($wallet?->balance ?? 0);
            $amount = (float) $lockedPurchase->amount_due;
            if ($wallet === null || $balance < $amount) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Số dư Clare Pay chưa đủ. Bạn còn thiếu '.number_format(max(0, $amount - $balance), 0, ',', '.').' VND.',
                ]);
            }

            $after = $balance - $amount;
            $wallet->update(['balance' => $after]);
            ClarePayTransaction::query()->create([
                'wallet_id' => $wallet->getKey(),
                'pay_later_purchase_id' => $lockedPurchase->getKey(),
                'type' => 'pay_later_payment',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_before' => $balance,
                'balance_after' => $after,
                'status' => 'completed',
                'provider' => 'clare_pay',
                'completed_at' => now(),
            ]);

            $lockedPurchase->update(['status' => 'processing', 'selected_payment_method' => 'clare_pay']);

            return $this->complete->execute($lockedPurchase, 'clare_pay');
        });
    }
}
