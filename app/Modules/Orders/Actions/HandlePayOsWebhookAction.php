<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentWebhookEvent;
use Illuminate\Validation\ValidationException;

class HandlePayOsWebhookAction
{
    public function __construct(
        private readonly PayOsClient $client,
        private readonly ConfirmPayOsPaymentAction $confirmPayment,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): PaymentWebhookEvent
    {
        try {
            $data = $this->client->verifyWebhook($payload);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['payos' => 'Chữ ký webhook payOS không hợp lệ.']);
        }

        $orderCode = (string) ($data['orderCode'] ?? '');
        if ($orderCode === '') {
            throw ValidationException::withMessages(['payos' => 'Webhook payOS thiếu orderCode.']);
        }

        $eventId = (string) ($data['reference'] ?? '');
        if ($eventId === '') {
            $eventId = hash('sha256', $orderCode.'|'.(string) ($data['paymentLinkId'] ?? '').'|'.(string) ($data['code'] ?? ''));
        }

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['provider' => 'payos', 'event_id' => $eventId],
            ['event_type' => 'payment.webhook', 'status' => 'received', 'payload' => $payload],
        );

        if (in_array($event->status, ['processed', 'ignored'], true)) {
            return $event;
        }

        $payment = Payment::query()
            ->where('provider', 'payos')
            ->where('provider_reference', $orderCode)
            ->first();

        if ($payment === null) {
            $event->update([
                'status' => 'ignored',
                'processed_at' => now(),
                'failure_reason' => 'Không tìm thấy giao dịch Clare tương ứng.',
            ]);

            return $event->fresh();
        }

        $event->update(['payment_id' => $payment->getKey(), 'status' => 'processing']);

        try {
            if ((string) ($data['code'] ?? '') === '00') {
                $this->confirmPayment->execute(
                    payment: $payment,
                    transactionId: (string) ($data['reference'] ?? $data['paymentLinkId'] ?? $orderCode),
                    amount: (int) ($data['amount'] ?? 0),
                );
                $event->update(['status' => 'processed', 'processed_at' => now(), 'failure_reason' => null]);
            } else {
                $event->update([
                    'status' => 'ignored',
                    'processed_at' => now(),
                    'failure_reason' => (string) ($data['desc'] ?? 'payOS không xác nhận thanh toán.'),
                ]);
            }
        } catch (\Throwable $exception) {
            $event->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }

        return $event->fresh();
    }
}
