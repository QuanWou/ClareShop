<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayLaterNotificationEvent extends Model
{
    protected $fillable = ['pay_later_purchase_id', 'event_key', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PayLaterPurchase::class, 'pay_later_purchase_id');
    }
}
