<?php

namespace App\Modules\Promotions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotion_code_id',
        'used_count',
        'claimed_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_count' => 'integer',
            'claimed_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promotionCode(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VoucherReservation::class);
    }
}
