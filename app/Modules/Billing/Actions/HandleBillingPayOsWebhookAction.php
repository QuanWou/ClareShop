<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\BillingPaymentAttempt;

class HandleBillingPayOsWebhookAction
{
    public function __construct(
        private readonly ConfirmBillingPayOsPaymentAction $confirm,
        private readonly DispatchPayLaterNotificationAction $notify,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): ?BillingPaymentAttempt
    {
        $orderCode = (string) ($data['orderCode'] ?? '');
        $attempt = BillingPaymentAttempt::query()->where('provider', 'payos')->where('provider_reference', $orderCode)->first();
        if ($attempt === null) {
            return $attempt;
        }

        if ((string) ($data['code'] ?? '') !== '00') {
            if ($attempt->status !== 'paid') {
                $reason = (string) ($data['desc'] ?? 'PayOS không xác nhận giao dịch.');
                $attempt->update(['status' => 'failed', 'failure_reason' => $reason]);
                $attempt->clarePayTransaction?->update(['status' => 'failed']);
                if ($attempt->payLaterPurchase) {
                    $attempt->payLaterPurchase->update(['status' => 'failed', 'latest_failure_reason' => $reason]);
                    $this->notify->execute($attempt->payLaterPurchase->fresh(['user', 'order']), 'failed');
                }
            }

            return $attempt->fresh();
        }

        return $this->confirm->execute(
            $attempt,
            (string) ($data['reference'] ?? $data['paymentLinkId'] ?? $orderCode),
            (int) ($data['amount'] ?? 0),
        );
    }
}
