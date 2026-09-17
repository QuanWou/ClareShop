<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPaymentAttempt extends Model
{
    protected $fillable = ['user_id', 'pay_later_purchase_id', 'clare_pay_transaction_id', 'purpose', 'provider', 'provider_reference', 'amount', 'currency', 'status', 'approval_url', 'payload', 'expires_at', 'paid_at', 'failure_reason'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payLaterPurchase(): BelongsTo
    {
        return $this->belongsTo(PayLaterPurchase::class);
    }

    public function clarePayTransaction(): BelongsTo
    {
        return $this->belongsTo(ClarePayTransaction::class);
    }
}
