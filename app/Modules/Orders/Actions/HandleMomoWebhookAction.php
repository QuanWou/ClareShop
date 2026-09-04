<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\MomoClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandleMomoWebhookAction
{
    public function __construct(
        private readonly MomoClient $client,
        private readonly ConfirmMomoPaymentAction $confirmPayment,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): PaymentWebhookEvent
    {
        if (! $this->client->verifiesCallback($payload)) {
            throw ValidationException::withMessages(['momo' => 'Chữ ký IPN MoMo không hợp lệ.']);
        }

        $transactionId = (string) ($payload['transId'] ?? '');
        $orderId = (string) ($payload['orderId'] ?? '');

        if ($transactionId === '' || $orderId === '') {
            throw ValidationException::withMessages(['momo' => 'IPN MoMo thiếu mã giao dịch hoặc mã đơn.']);
        }

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['provider' => 'momo', 'event_id' => $transactionId],
            ['event_type' => 'payment.ipn', 'status' => 'received', 'payload' => $payload],
        );

        if (in_array($event->status, ['processed', 'ignored'], true)) {
            return $event;
        }

        $payment = Payment::query()->where('provider', 'momo')->where('provider_reference', $orderId)->first();

        if ($payment === null) {
            $event->update(['status' => 'ignored', 'processed_at' => now(), 'failure_reason' => 'Không tìm thấy giao dịch Clare tương ứng.']);
            return $event->fresh();
        }

        $event->update(['payment_id' => $payment->getKey(), 'status' => 'processing']);

        try {
            if ((int) ($payload['resultCode'] ?? -1) === 0) {
                $this->confirmPayment->execute($payment, $transactionId, (int) ($payload['amount'] ?? 0));
                $event->update(['status' => 'processed', 'processed_at' => now(), 'failure_reason' => null]);
            } else {
                $event->update(['status' => 'ignored', 'processed_at' => now(), 'failure_reason' => (string) ($payload['message'] ?? 'MoMo không xác nhận thanh toán.')]);
            }
        } catch (\Throwable $exception) {
            $event->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }

        return $event->fresh();
    }
}
