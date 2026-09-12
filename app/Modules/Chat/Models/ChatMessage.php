<?php

namespace App\Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_BOT = 'bot';

    public const SENDER_ADMIN = 'admin';

    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'client_message_id',
        'message',
        'message_type',
        'metadata',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_read' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
