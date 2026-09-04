<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Mail\OrderPlacedMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Actions\ConfigureStoreMailAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationAction
{
    public function __construct(private readonly ConfigureStoreMailAction $configureMail) {}

    public function execute(Order $order, bool $force = false): bool
    {
        $this->configureMail->execute();

        if (! $this->usesRealDeliveryTransport()) {
            return false;
        }

        $reservation = DB::transaction(function () use ($order, $force): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $previousSentAt = $lockedOrder->confirmation_email_sent_at;

            if (! $force && $previousSentAt !== null) {
                return ['send' => false, 'previous_sent_at' => $previousSentAt];
            }

            $lockedOrder->update(['confirmation_email_sent_at' => now()]);

            return ['send' => true, 'previous_sent_at' => $previousSentAt];
        });

        if (! $reservation['send']) {
            return true;
        }

        try {
            $freshOrder = $order->fresh(['items', 'discount', 'payments']);
            Mail::to($freshOrder->customer_email)->send(new OrderPlacedMail($freshOrder));

            return true;
        } catch (\Throwable $exception) {
            Order::query()
                ->whereKey($order->getKey())
                ->whereNotNull('confirmation_email_sent_at')
                ->update(['confirmation_email_sent_at' => $reservation['previous_sent_at']]);
            report($exception);

            return false;
        }
    }

    private function usesRealDeliveryTransport(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer === 'failover' && in_array('log', config('mail.mailers.failover.mailers', []), true)) {
            return false;
        }

        return true;
    }
}
