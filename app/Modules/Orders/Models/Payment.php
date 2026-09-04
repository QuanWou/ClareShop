<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'provider_transaction_id',
        'amount',
        'currency',
        'gateway_amount',
        'gateway_currency',
        'exchange_rate',
        'status',
        'paid_at',
        'expires_at',
        'webhook_confirmed_at',
        'failure_reason',
        'approval_url',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'webhook_confirmed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PaymentStatusHistory::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }
}
