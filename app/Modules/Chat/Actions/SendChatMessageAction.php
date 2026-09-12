<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Support\ChatMessageRouter;

class SendChatMessageAction
{
    public function __construct(
        private readonly BootstrapChatAction $bootstrap,
        private readonly ChatMessageRouter $router,
    ) {}

    public function execute(?User $customer, string $message, string $clientMessageId, bool $forceHandoff = false, ?int $productId = null): ChatConversation
    {
        $conversation = $this->bootstrap->execute($customer);
        $existing = $conversation->messages()->where('client_message_id', $clientMessageId)->first();
        if ($existing !== null) {
            return $existing->conversation;
        }

        if ($conversation->status === ChatConversation::STATUS_CLOSED) {
            $conversation->update([
                'status' => ChatConversation::STATUS_BOT,
                'assigned_admin_id' => null,
            ]);
            $conversation->messages()->create([
                'sender_type' => ChatMessage::SENDER_SYSTEM,
                'message' => 'Cuộc trò chuyện đã được mở lại.',
                'message_type' => 'system',
                'is_read' => true,
            ]);
        }

        $customerMessage = $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'sender_id' => $customer?->getKey(),
            'client_message_id' => $clientMessageId,
            'message' => $message,
            'message_type' => 'text',
            'is_read' => false,
        ]);
        $conversation->update(['last_message_at' => $customerMessage->created_at]);

        if (in_array($conversation->status, [ChatConversation::STATUS_ADMIN, ChatConversation::STATUS_WAITING_ADMIN], true)) {
            return $conversation;
        }

        $resolution = $forceHandoff
            ? ['reply' => new ChatReply('Mình đang kết nối bạn với nhân viên hỗ trợ.', 'handoff'), 'handoff' => true]
            : $this->router->route($message, $customer, $conversation, $productId);

        if ($resolution['handoff']) {
            $conversation->update([
                'status' => ChatConversation::STATUS_WAITING_ADMIN,
                'assigned_admin_id' => null,
            ]);
        }

        $reply = $resolution['reply'];
        $botMessage = $conversation->messages()->create([
            'sender_type' => $resolution['handoff'] ? ChatMessage::SENDER_SYSTEM : ChatMessage::SENDER_BOT,
            'message' => $reply->message,
            'message_type' => $reply->type,
            'metadata' => $reply->metadata ?: null,
            'is_read' => false,
        ]);
        $conversation->update(['last_message_at' => $botMessage->created_at]);

        return $conversation;
    }
}
