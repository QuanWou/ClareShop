<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\ClarePayTransaction;
use Illuminate\Support\Facades\DB;

class CreateClarePayTopUpAction
{
    public function __construct(
        private readonly ResolveClarePayWalletAction $resolveWallet,
        private readonly InitializeBillingPayOsPaymentAction $initializePayOs,
    ) {}

    public function execute(User $user, int $amount): BillingPaymentAttempt
    {
        $attempt = DB::transaction(function () use ($user, $amount): BillingPaymentAttempt {
            $wallet = $this->resolveWallet->execute($user);
            $transaction = ClarePayTransaction::query()->create([
                'wallet_id' => $wallet->getKey(),
                'type' => 'top_up',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
                'status' => 'pending',
                'provider' => 'payos',
            ]);

            return BillingPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'clare_pay_transaction_id' => $transaction->getKey(),
                'purpose' => 'clare_pay_top_up',
                'provider' => 'payos',
                'amount' => $amount,
                'currency' => 'VND',
                'status' => 'pending',
            ]);
        });

        return $this->initializePayOs->execute($attempt);
    }
}
