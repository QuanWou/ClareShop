<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Orders\Gateways\PayOsClient;
use Illuminate\Validation\ValidationException;

class SyncBillingPayOsPaymentAction
{
    public function __construct(
        private readonly PayOsClient $client,
        private readonly ConfirmBillingPayOsPaymentAction $confirm,
    ) {}

    public function execute(BillingPaymentAttempt $attempt): BillingPaymentAttempt
    {
        if ($attempt->provider !== 'payos' || blank($attempt->provider_reference)) {
            throw ValidationException::withMessages(['payment' => 'Phiên payOS chưa được khởi tạo hợp lệ.']);
        }

        $gateway = $this->client->getPayment((string) $attempt->provider_reference);
        if (strtoupper((string) ($gateway['status'] ?? '')) !== 'PAID') {
            return $attempt->fresh();
        }

        $transactionId = (string) data_get($gateway, 'transactions.0.reference', $gateway['id'] ?? $attempt->provider_reference);
        $amount = (int) ($gateway['amountPaid'] ?? $gateway['amount'] ?? 0);

        return $this->confirm->execute($attempt, $transactionId, $amount);
    }
}
