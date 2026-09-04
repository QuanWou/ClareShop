<?php

namespace App\Modules\Orders\Models;

use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDiscount extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'promotion_code_id',
        'user_voucher_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function promotionCode(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class);
    }

    public function userVoucher(): BelongsTo
    {
        return $this->belongsTo(UserVoucher::class);
    }
}
