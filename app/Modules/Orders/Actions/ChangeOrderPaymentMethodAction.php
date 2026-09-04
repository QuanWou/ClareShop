<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeOrderPaymentMethodAction
{
    public function __construct(
        private readonly CreatePaymentAttemptAction $createPaymentAttempt,
        private readonly InitializePayPalPaymentAction $initializePayPalPayment,
        private readonly InitializeMomoPaymentAction $initializeMomoPayment,
        private readonly InitializePayOsPaymentAction $initializePayOsPayment,
    ) {}

    public function execute(User $customer, Order $order, string $paymentMethodCode): Payment
    {
        $payment = DB::transaction(function () use ($customer, $order, $paymentMethodCode): Payment {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->where('user_id', $customer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($paymentMethodCode, PaymentMethodCatalog::codes(), true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Phương thức thanh toán không hợp lệ.',
                ]);
            }

            if (! $lockedOrder->canCustomerChangePaymentMethod()) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Đơn hiện không thể đổi phương thức thanh toán. Phiên đang hoạt động cần được hủy hoặc hết hạn trước.',
                ]);
            }

            if ($lockedOrder->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Đơn đã có giao dịch thành công nên không thể đổi phương thức thanh toán.',
                ]);
            }

            $reason = 'Phiên thanh toán đã được thay thế do khách hàng đổi phương thức.';
            $activePayments = Payment::query()
                ->where('order_id', $lockedOrder->getKey())
                ->whereIn('status', ['unpaid', 'pending'])
                ->lockForUpdate()
                ->get();

            foreach ($activePayments as $activePayment) {
                $previousStatus = $activePayment->status;
                $activePayment->update([
                    'status' => 'expired',
                    'failure_reason' => $reason,
                    'approval_url' => null,
                    'expires_at' => now(),
                ]);

                PaymentStatusHistory::query()->create([
                    'payment_id' => $activePayment->getKey(),
                    'from_status' => $previousStatus,
                    'to_status' => 'expired',
                    'changed_by' => $customer->getKey(),
                    'note' => $reason,
                ]);
            }

            $paymentMethod = PaymentMethodCatalog::get($paymentMethodCode);
            $lockedOrder->update([
                'payment_method' => $paymentMethodCode,
                'payment_status' => $paymentMethod['initial_status'],
            ]);

            return $this->createPaymentAttempt->execute(
                order: $lockedOrder,
                paymentMethodCode: $paymentMethodCode,
                actorId: (int) $customer->getKey(),
                historyNote: 'Khách hàng đã chọn phương thức thanh toán khác.',
            );
        });

        return match ($payment->provider) {
            'paypal' => $this->initializePayPalPayment->execute($payment),
            'momo' => (bool) config('services.momo.enabled') && ! app()->runningUnitTests()
                ? $this->initializeMomoPayment->execute($payment)
                : $payment,
            'payos' => (bool) config('services.payos.enabled')
                ? $this->initializePayOsPayment->execute($payment)
                : $payment,
            default => $payment,
        };
    }
}
