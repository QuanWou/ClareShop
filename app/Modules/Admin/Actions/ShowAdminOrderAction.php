<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Orders\Models\Order;

class ShowAdminOrderAction
{
    public function execute(Order $order): Order
    {
        return $order->load([
            'items.variant',
            'discount',
            'payments.statusHistories.changedBy',
            'statusHistories.changedBy',
        ]);
    }
}
