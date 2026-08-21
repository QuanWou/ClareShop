<?php

namespace App\Modules\Catalog\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'user_id', 'order_id', 'rating', 'title', 'comment', 'status', 'is_verified_purchase', 'moderation_note', 'approved_at'])]
class ProductReview extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductReviewImage::class)->orderBy('sort_order');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Đang hiển thị',
            'hidden' => 'Đã ẩn',
            default => 'Chờ duyệt',
        };
    }
}
