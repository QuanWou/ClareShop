<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;

class ShowCustomerOrderAction
{
    public function execute(User $user, Order $order): Order
    {
        return Order::query()
            ->whereKey($order->getKey())
            ->where('user_id', $user->getKey())
            ->with([
                'items.variant.images',
                'items.variant.product.images',
                'payments',
                'discount',
                'statusHistories',
            ])
            ->firstOrFail();
    }
}
