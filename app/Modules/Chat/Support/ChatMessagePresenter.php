<?php

namespace App\Modules\Chat\Support;

use App\Modules\Chat\Models\ChatMessage;

class ChatMessagePresenter
{
    /** @return array<string, mixed> */
    public function present(ChatMessage $message): array
    {
        $metadata = $message->metadata ?? [];
        $safeMetadata = array_intersect_key($metadata, array_flip([
            'products', 'order', 'vouchers', 'more_url', 'action_url', 'action_label', 'show_handoff',
        ]));

        return [
            'id' => $message->getKey(),
            'sender' => $message->sender_type,
            'sender_name' => $message->sender_type === ChatMessage::SENDER_ADMIN
                ? ($message->sender?->name ?? 'Nhân viên Clare')
                : null,
            'type' => $message->message_type,
            'text' => $message->message,
            'metadata' => $safeMetadata,
            'time' => $message->created_at?->format('H:i'),
        ];
    }
}
