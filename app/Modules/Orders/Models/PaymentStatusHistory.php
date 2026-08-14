<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'payment_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
