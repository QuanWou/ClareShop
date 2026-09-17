<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayLaterPurchase extends Model
{
    public const PAYABLE_STATUSES = ['due', 'overdue', 'failed'];

    protected $fillable = ['user_id', 'order_id', 'total_amount', 'term_months', 'starts_at', 'due_at', 'amount_due', 'selected_payment_method', 'paid_at', 'status', 'latest_failure_reason'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(BillingPaymentAttempt::class);
    }

    public function canPayNow(): bool
    {
        return in_array($this->status, self::PAYABLE_STATUSES, true) && (float) $this->amount_due > 0;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Chưa đến hạn',
            'due_soon' => 'Sắp đến hạn',
            'due' => 'Đến hạn',
            'processing' => 'Đang thanh toán',
            'paid' => 'Đã thanh toán',
            'overdue' => 'Quá hạn',
            'failed' => 'Thanh toán thất bại',
            'cancelled' => 'Đã hủy',
            default => $this->status,
        };
    }
}
