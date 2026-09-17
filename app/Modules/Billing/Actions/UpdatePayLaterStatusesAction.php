<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\PayLaterPurchase;

class UpdatePayLaterStatusesAction
{
    public function __construct(private readonly DispatchPayLaterNotificationAction $notify) {}

    public function execute(): int
    {
        $processed = 0;

        PayLaterPurchase::query()
            ->whereIn('status', ['active', 'due_soon', 'due', 'overdue', 'failed'])
            ->with(['user', 'order'])
            ->orderBy('id')
            ->chunkById(100, function ($purchases) use (&$processed): void {
                foreach ($purchases as $purchase) {
                    $now = now();
                    $dueAt = $purchase->due_at;

                    if ($now->gte($dueAt->copy()->subMonth()) && $now->lt($dueAt->copy()->subDays(7))) {
                        $this->notify->execute($purchase, 'month_before');
                    }
                    if ($now->gte($dueAt->copy()->subDays(7)) && $now->lt($dueAt->copy()->startOfDay())) {
                        $this->notify->execute($purchase, 'seven_days_before');
                    }

                    $nextStatus = $now->gt($dueAt->copy()->endOfDay())
                        ? 'overdue'
                        : ($now->gte($dueAt->copy()->startOfDay())
                            ? 'due'
                            : ($now->gte($dueAt->copy()->subDays(7)) ? 'due_soon' : 'active'));

                    if ($purchase->status !== 'failed' && $purchase->status !== $nextStatus) {
                        $purchase->update(['status' => $nextStatus]);
                    }

                    if ($nextStatus === 'due') {
                        $this->notify->execute($purchase->fresh(['user', 'order']), 'due');
                    } elseif ($nextStatus === 'overdue') {
                        $this->notify->execute($purchase->fresh(['user', 'order']), 'overdue');
                    }

                    $processed++;
                }
            });

        return $processed;
    }
}
