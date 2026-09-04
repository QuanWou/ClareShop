<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Payment;

class FailPayPalPaymentAction
{
    public function __construct(private readonly FailPaymentAttemptAction $failPaymentAttempt) {}

    public function execute(Payment $payment, string $reason, string $status = 'failed'): Payment
    {
        return $this->failPaymentAttempt->execute($payment, $reason, $status);
    }
}
