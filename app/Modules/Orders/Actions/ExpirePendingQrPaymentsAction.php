<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Support\Facades\DB;

class ExpirePendingQrPaymentsAction
{
    public function __construct(
        private readonly SyncPayOsPaymentAction $syncPayOsPayment,
    ) {}

    /** @return int Number of QR sessions expired in this pass. */
    public function execute(): int
    {
        $expired = 0;

        Payment::query()
            ->with('order')
            ->whereIn('provider', ['momo', 'vnpay', 'payos'])
            ->whereIn('status', ['pending', 'unpaid'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$expired): void {
                foreach ($payments as $payment) {
                    if ($payment->provider === 'payos'
                        && $payment->order?->status === 'pending'
                        && $payment->order?->payment_method === 'bank_transfer') {
                        try {
                            $synced = $this->syncPayOsPayment->execute($payment);

                            if ($synced->status === 'paid') {
                                continue;
                            }
                        } catch (\Throwable $exception) {
                            // Keep the payment retryable when PayOS is temporarily
                            // unavailable; a late webhook or the next scheduler
                            // pass may still confirm a transfer that was paid.
                            report($exception);
                            continue;
                        }
                    }

                    DB::transaction(function () use ($payment): void {
                        $locked = Payment::query()->lockForUpdate()->find($payment->getKey());
                        if ($locked === null || ! in_array($locked->status, ['pending', 'unpaid'], true) || ! ($locked->expires_at?->isPast() ?? false)) {
                            return;
                        }
                        $previousStatus = $locked->status;
                        $locked->update(['status' => 'expired', 'failure_reason' => 'Phiên thanh toán QR đã hết hạn.']);
                        $locked->order()->update(['payment_status' => 'expired']);
                        PaymentStatusHistory::query()->create(['payment_id' => $locked->getKey(), 'from_status' => $previousStatus, 'to_status' => 'expired', 'changed_by' => null, 'note' => 'Phiên thanh toán QR hết hạn sau 3 phút.']);
                    });
                    $expired++;
                }
            });

        return $expired;
    }
}
