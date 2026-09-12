<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Models\ChatConversation;
use Illuminate\Support\Str;

class ResolveChatConversationAction
{
    public function execute(?User $customer): ChatConversation
    {
        $guestId = session('clare_chat_guest_id');
        if (! is_string($guestId) || ! Str::isUuid($guestId)) {
            $guestId = (string) Str::uuid();
            session(['clare_chat_guest_id' => $guestId]);
        }

        if ($customer !== null) {
            $conversation = ChatConversation::query()
                ->where('customer_id', $customer->getKey())
                ->latest('last_message_at')
                ->latest('id')
                ->first();

            if ($conversation !== null) {
                return $conversation;
            }

            $guestConversation = ChatConversation::query()
                ->whereNull('customer_id')
                ->where('guest_session_id', $guestId)
                ->latest('id')
                ->first();

            if ($guestConversation !== null) {
                $guestConversation->update(['customer_id' => $customer->getKey()]);

                return $guestConversation;
            }
        } else {
            $conversation = ChatConversation::query()
                ->whereNull('customer_id')
                ->where('guest_session_id', $guestId)
                ->latest('last_message_at')
                ->latest('id')
                ->first();

            if ($conversation !== null) {
                return $conversation;
            }
        }

        return ChatConversation::query()->create([
            'customer_id' => $customer?->getKey(),
            'guest_session_id' => $guestId,
            'status' => ChatConversation::STATUS_BOT,
            'last_message_at' => now(),
        ]);
    }
}
