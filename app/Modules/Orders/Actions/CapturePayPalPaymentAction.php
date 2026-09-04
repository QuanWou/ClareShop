<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayPalClient;
use App\Modules\Orders\Models\Payment;
use Illuminate\Validation\ValidationException;

class CapturePayPalPaymentAction
{
    public function __construct(
        private readonly PayPalClient $client,
        private readonly ConfirmPayPalPaymentAction $confirmPayment,
    ) {}

    public function execute(Payment $payment, string $source = 'return'): Payment
    {
        $payment = Payment::query()->findOrFail($payment->getKey());

        if ($payment->status === 'paid') {
            return $payment;
        }

        if ($payment->expires_at?->isPast()) {
            throw ValidationException::withMessages(['payment' => 'Giao dịch PayPal đã hết hạn. Vui lòng khởi tạo lại.']);
        }

        if (blank($payment->provider_reference)) {
            throw ValidationException::withMessages(['payment' => 'Giao dịch PayPal chưa được khởi tạo.']);
        }

        $captureResponse = $this->client->captureOrder((string) $payment->provider_reference, (int) $payment->getKey());
        $capture = data_get($captureResponse, 'purchase_units.0.payments.captures.0');

        if (data_get($capture, 'status') !== 'COMPLETED') {
            throw ValidationException::withMessages(['payment' => 'PayPal chưa xác nhận giao dịch hoàn tất.']);
        }

        return $this->confirmPayment->execute(
            payment: $payment,
            transactionId: (string) data_get($capture, 'id'),
            amount: (string) data_get($capture, 'amount.value'),
            currency: (string) data_get($capture, 'amount.currency_code'),
            source: $source,
        );
    }
}
