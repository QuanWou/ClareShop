<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Orders\Models\Order;
use Illuminate\Validation\ValidationException;

class CreatePayLaterPurchaseAction
{
    public function execute(Order $order, User $user, int $termMonths): PayLaterPurchase
    {
        if (! in_array($termMonths, [2, 4], true)) {
            throw ValidationException::withMessages(['pay_later_term_months' => 'Thời hạn trả sau chỉ có thể là 2 hoặc 4 tháng.']);
        }

        $startsAt = $order->placed_at ?? now();

        return PayLaterPurchase::query()->create([
            'user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'total_amount' => $order->total,
            'term_months' => $termMonths,
            'starts_at' => $startsAt,
            'due_at' => $startsAt->copy()->addMonthsNoOverflow($termMonths),
            'amount_due' => $order->total,
            'status' => 'active',
        ]);
    }
}
