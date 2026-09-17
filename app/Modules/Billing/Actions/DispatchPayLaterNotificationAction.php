<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\PayLaterNotificationEvent;
use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Billing\Notifications\PayLaterNotice;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use Illuminate\Database\QueryException;

class DispatchPayLaterNotificationAction
{
    public function execute(PayLaterPurchase $purchase, string $eventKey): bool
    {
        $purchase->loadMissing(['user', 'order']);

        try {
            $event = PayLaterNotificationEvent::query()->create([
                'pay_later_purchase_id' => $purchase->getKey(),
                'event_key' => $eventKey,
            ]);
        } catch (QueryException) {
            return false;
        }

        $purchase->user->notify(new PayLaterNotice($purchase, $eventKey));

        $conversation = ChatConversation::query()
            ->where('customer_id', $purchase->user_id)
            ->latest('last_message_at')
            ->first() ?? ChatConversation::query()->create([
                'customer_id' => $purchase->user_id,
                'status' => ChatConversation::STATUS_BOT,
            ]);

        $notification = (new PayLaterNotice($purchase, $eventKey))->toArray($purchase->user);
        $message = $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_SYSTEM,
            'message' => $notification['title'].': '.$notification['message'],
            'message_type' => 'system',
            'metadata' => [
                'action_url' => $notification['action_url'],
                'action_label' => $notification['action_label'],
            ],
            'is_read' => false,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);
        $event->update(['sent_at' => now()]);

        return true;
    }
}
