<?php

namespace App\Modules\Chat\Data;

final readonly class ChatReply
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $message,
        public string $type = 'text',
        public array $metadata = [],
    ) {}
}
