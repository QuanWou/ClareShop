<?php

namespace App\Modules\Promotions\Models;

use App\Modules\Orders\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'banner_path',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'claim_limit',
        'claim_count',
        'per_user_usage_limit',
        'is_active',
        'is_public',
        'requires_claim',
        'application_scope',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_order_amount' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'claim_limit' => 'integer',
            'claim_count' => 'integer',
            'per_user_usage_limit' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'requires_claim' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function orderDiscounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function userVouchers(): HasMany
    {
        return $this->hasMany(UserVoucher::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VoucherReservation::class);
    }

    public function isClaimableNow(): bool
    {
        return $this->is_public
            && $this->is_active
            && ! ($this->starts_at?->isFuture() ?? false)
            && ! ($this->ends_at?->isPast() ?? false)
            && ($this->claim_limit === null || $this->claim_count < $this->claim_limit);
    }
}
