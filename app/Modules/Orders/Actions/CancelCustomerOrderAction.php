<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Validation\ValidationException;

class CancelCustomerOrderAction
{
    public function __construct(private readonly TransitionOrderStatusAction $transitionOrderStatus) {}

    public function execute(User $customer, Order $order, string $reason): Order
    {
        $order = Order::query()
            ->whereKey($order->getKey())
            ->where('user_id', $customer->getKey())
            ->firstOrFail();

        if (! $order->canCustomerCancel()) {
            throw ValidationException::withMessages([
                'cancel_reason' => 'Đơn hiện không thể hủy. Phiên thanh toán đang hoạt động cần được hủy hoặc hết hạn trước.',
            ]);
        }

        return $this->transitionOrderStatus->execute(
            order: $order,
            actorId: (int) $customer->getKey(),
            nextStatus: 'cancelled',
            note: null,
            cancelReason: $reason,
        );
    }
}
