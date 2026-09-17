<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\ClarePayTransaction;
use App\Modules\Billing\Models\ClarePayWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmBillingPayOsPaymentAction
{
    public function __construct(private readonly CompletePayLaterPurchaseAction $completePayLater) {}

    public function execute(BillingPaymentAttempt $attempt, string $transactionId, int $amount): BillingPaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $transactionId, $amount): BillingPaymentAttempt {
            $locked = BillingPaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());
            if ($locked->status === 'paid') {
                return $locked;
            }
            if ($locked->provider !== 'payos' || $amount !== (int) $locked->amount) {
                throw ValidationException::withMessages(['payment' => 'Giao dịch payOS không hợp lệ hoặc sai số tiền.']);
            }

            if ($locked->purpose === 'clare_pay_top_up') {
                $transaction = ClarePayTransaction::query()->lockForUpdate()->findOrFail($locked->clare_pay_transaction_id);
                if ($transaction->status !== 'completed') {
                    $wallet = ClarePayWallet::query()->lockForUpdate()->findOrFail($transaction->wallet_id);
                    $before = (float) $wallet->balance;
                    $after = $before + (float) $locked->amount;
                    $wallet->update(['balance' => $after]);
                    $transaction->update([
                        'status' => 'completed',
                        'provider_reference' => $transactionId,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'completed_at' => now(),
                    ]);
                }
            } elseif ($locked->purpose === 'pay_later') {
                $payLater = $locked->payLaterPurchase()->firstOrFail();
            } else {
                throw ValidationException::withMessages(['payment' => 'Mục đích thanh toán payOS không hợp lệ.']);
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'failure_reason' => null,
                'payload' => [...($locked->payload ?? []), 'transaction_id' => $transactionId],
            ]);

            if (isset($payLater)) {
                $this->completePayLater->execute($payLater, 'payos');
            }

            return $locked->fresh();
        });
    }
}
