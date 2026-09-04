<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayPalClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Orders\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandlePayPalWebhookAction
{
    public function __construct(
        private readonly PayPalClient $client,
        private readonly CapturePayPalPaymentAction $capturePayment,
        private readonly ConfirmPayPalPaymentAction $confirmPayment,
        private readonly FailPayPalPaymentAction $failPayment,
    ) {}

    /** @param array<string, string|null> $headers @param array<string, mixed> $payload */
    public function execute(array $headers, array $payload): PaymentWebhookEvent
    {
        if (! $this->client->verifyWebhook($headers, $payload)) {
            throw ValidationException::withMessages(['paypal' => 'Chữ ký webhook PayPal không hợp lệ.']);
        }

        $eventId = data_get($payload, 'id');
        $eventType = data_get($payload, 'event_type');

        if (! is_string($eventId) || ! is_string($eventType)) {
            throw ValidationException::withMessages(['paypal' => 'Webhook PayPal thiếu mã hoặc loại sự kiện.']);
        }

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['provider' => 'paypal', 'event_id' => $eventId],
            ['event_type' => $eventType, 'status' => 'received', 'payload' => $payload],
        );

        if ($event->status === 'processed' || $event->status === 'ignored') {
            return $event;
        }

        try {
            $payment = $this->resolvePayment($payload);

            if ($payment === null) {
                $event->update([
                    'status' => 'ignored',
                    'processed_at' => now(),
                    'failure_reason' => 'Không tìm thấy giao dịch Clare tương ứng.',
                ]);

                return $event->fresh();
            }

            $event->update(['payment_id' => $payment->getKey(), 'status' => 'processing']);
            $this->processEvent($eventType, $payment, $payload);
            $event->update(['status' => 'processed', 'processed_at' => now(), 'failure_reason' => null]);

            return $event->fresh();
        } catch (\Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $payload */
    private function resolvePayment(array $payload): ?Payment
    {
        $providerOrderId = data_get($payload, 'resource.supplementary_data.related_ids.order_id')
            ?? data_get($payload, 'resource.id');

        if (is_string($providerOrderId)) {
            $payment = Payment::query()
                ->where('provider', 'paypal')
                ->where('provider_reference', $providerOrderId)
                ->first();

            if ($payment !== null) {
                return $payment;
            }
        }

        $paymentId = data_get($payload, 'resource.purchase_units.0.custom_id');

        return is_numeric($paymentId)
            ? Payment::query()->where('provider', 'paypal')->find((int) $paymentId)
            : null;
    }

    /** @param array<string, mixed> $payload */
    private function processEvent(string $eventType, Payment $payment, array $payload): void
    {
        match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $this->capturePayment->execute($payment, 'webhook'),
            'PAYMENT.CAPTURE.COMPLETED' => $this->confirmPayment->execute(
                payment: $payment,
                transactionId: (string) data_get($payload, 'resource.id'),
                amount: (string) data_get($payload, 'resource.amount.value'),
                currency: (string) data_get($payload, 'resource.amount.currency_code'),
                source: 'webhook',
            ),
            'PAYMENT.CAPTURE.DENIED' => $this->failPayment->execute(
                $payment,
                'PayPal thông báo giao dịch bị từ chối.',
            ),
            'PAYMENT.CAPTURE.REFUNDED' => $this->recordRefund($payment),
            default => null,
        };
    }

    private function recordRefund(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($lockedPayment->status === 'refunded') {
                return;
            }

            $previousStatus = $lockedPayment->status;
            $lockedPayment->update(['status' => 'refunded']);
            $lockedPayment->order()->update(['payment_status' => 'refunded']);

            PaymentStatusHistory::query()->create([
                'payment_id' => $lockedPayment->getKey(),
                'from_status' => $previousStatus,
                'to_status' => 'refunded',
                'changed_by' => null,
                'note' => 'PayPal webhook đã xác nhận hoàn tiền.',
            ]);
        });
    }
}
