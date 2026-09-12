<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;

interface WebsiteChatResolver
{
    public function supports(string $message): bool;

    public function resolve(string $message, ?User $customer, ChatConversation $conversation): ChatReply;
}
