<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Support\ChatSettingsRegistry;

class BootstrapChatAction
{
    public function __construct(
        private readonly ResolveChatConversationAction $resolveConversation,
        private readonly ChatSettingsRegistry $settings,
    ) {}

    public function execute(?User $customer): ChatConversation
    {
        $conversation = $this->resolveConversation->execute($customer);

        if (! $conversation->messages()->exists()) {
            $conversation->messages()->create([
                'sender_type' => ChatMessage::SENDER_BOT,
                'message' => $this->settings->get('welcome_message'),
                'message_type' => 'text',
                'is_read' => true,
            ]);
        }

        return $conversation;
    }
}
