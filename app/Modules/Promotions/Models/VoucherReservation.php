<?php

namespace App\Modules\Promotions\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherReservation extends Model
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'promotion_code_id',
        'user_id',
        'user_voucher_id',
        'order_id',
        'status',
        'discount_amount',
        'reserved_at',
        'expires_at',
        'redeemed_at',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function promotionCode(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userVoucher(): BelongsTo
    {
        return $this->belongsTo(UserVoucher::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isActiveReservation(): bool
    {
        return $this->status === self::STATUS_RESERVED
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
