<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Payment;

class ReconcilePendingPayOsPaymentsAction
{
    public function __construct(
        private readonly SyncPayOsPaymentAction $syncPayment,
    ) {}

    /** @return array{checked: int, confirmed: int, failed: int} */
    public function execute(): array
    {
        $result = ['checked' => 0, 'confirmed' => 0, 'failed' => 0];

        Payment::query()
            ->with('order')
            ->where('provider', 'payos')
            ->whereIn('status', ['pending', 'unpaid'])
            ->whereHas('order', fn ($query) => $query
                ->where('status', 'pending')
                ->where('payment_method', 'bank_transfer'))
            ->latest('id')
            ->limit(5)
            ->get()
            ->each(function (Payment $payment) use (&$result): void {
                $result['checked']++;

                try {
                    if ($this->syncPayment->execute($payment)->status === 'paid') {
                        $result['confirmed']++;
                    }
                } catch (\Throwable) {
                    // Browser polling and the signed return URL remain active.
                    // The next scheduler pass retries transient gateway errors.
                    $result['failed']++;
                }
            });

        return $result;
    }
}
