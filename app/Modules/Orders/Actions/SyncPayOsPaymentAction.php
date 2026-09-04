<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Payment;
use Illuminate\Validation\ValidationException;

class SyncPayOsPaymentAction
{
    public function __construct(
        private readonly PayOsClient $client,
        private readonly ConfirmPayOsPaymentAction $confirmPayment,
    ) {}

    public function execute(Payment $payment): Payment
    {
        $payment = Payment::query()->findOrFail($payment->getKey());

        if ($payment->status === 'paid') {
            return $payment;
        }

        if ($payment->provider !== 'payos' || blank($payment->provider_reference)) {
            throw ValidationException::withMessages(['payment' => 'Giao dịch payOS chưa được khởi tạo hợp lệ.']);
        }

        $gatewayPayment = $this->client->getPayment((string) $payment->provider_reference);

        if (mb_strtoupper((string) ($gatewayPayment['status'] ?? '')) !== 'PAID') {
            return $payment;
        }

        $transactionId = (string) (data_get($gatewayPayment, 'transactions.0.reference')
            ?? $gatewayPayment['id']
            ?? data_get($payment->payload, 'payment_link_id')
            ?? $payment->provider_reference);

        return $this->confirmPayment->execute(
            payment: $payment,
            transactionId: $transactionId,
            amount: (int) ($gatewayPayment['amountPaid'] ?? $gatewayPayment['amount'] ?? 0),
            source: 'return',
        );
    }
}
