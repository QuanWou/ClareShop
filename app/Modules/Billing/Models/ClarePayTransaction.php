<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClarePayTransaction extends Model
{
    protected $fillable = ['wallet_id', 'pay_later_purchase_id', 'type', 'direction', 'amount', 'balance_before', 'balance_after', 'status', 'provider', 'provider_reference', 'payload', 'completed_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ClarePayWallet::class, 'wallet_id');
    }

    public function payLaterPurchase(): BelongsTo
    {
        return $this->belongsTo(PayLaterPurchase::class);
    }
}
