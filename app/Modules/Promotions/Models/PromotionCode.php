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
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function orderDiscounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }
}
