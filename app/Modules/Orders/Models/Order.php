<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\Promotions\Models\VoucherReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'currency',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_recipient_name',
        'shipping_phone',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_ward',
        'shipping_district',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country_code',
        'shipping_provider',
        'shipping_service',
        'shipping_quote_id',
        'shipping_tracking_number',
        'shipping_quote_payload',
        'shipping_total_weight_grams',
        'shipping_estimated_days',
        'shipping_fee_is_estimated',
        'subtotal',
        'shipping_fee',
        'discount_total',
        'total',
        'customer_note',
        'admin_note',
        'cancel_reason',
        'placed_at',
        'estimated_delivery_at',
        'confirmed_at',
        'preparing_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'confirmation_email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_quote_payload' => 'array',
            'shipping_fee_is_estimated' => 'boolean',
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'preparing_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmation_email_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function discount(): HasOne
    {
        return $this->hasOne(OrderDiscount::class);
    }

    public function voucherReservation(): HasOne
    {
        return $this->hasOne(VoucherReservation::class);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public static function statusLabelFor(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Chờ lấy hàng',
            'processing' => 'Đang chuẩn bị giao',
            'shipped' => 'Đang giao hàng',
            'completed' => 'Đã giao',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Chờ thanh toán/xét duyệt',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            'failed' => 'Thanh toán thất bại',
            'expired' => 'Đã hết hạn thanh toán',
            default => $this->payment_status,
        };
    }

    public function canCustomerChangePaymentMethod(): bool
    {
        return $this->status === 'pending'
            && (in_array($this->payment_status, ['unpaid', 'failed', 'expired'], true)
                || ($this->payment_method === 'pay_later' && $this->payment_status === 'pending'));
    }

    public function canCustomerCancel(): bool
    {
        return $this->canCustomerChangePaymentMethod();
    }

    public function estimatedDeliveryDate(): ?Carbon
    {
        if ($this->estimated_delivery_at instanceof Carbon) {
            return $this->estimated_delivery_at;
        }

        if (! $this->placed_at instanceof Carbon || $this->shipping_estimated_days === null) {
            return null;
        }

        return $this->placed_at->copy()->addWeekdays($this->shipping_estimated_days);
    }

    /** @return array<int, array{status: string, label: string, description: string, at: ?Carbon, complete: bool, current: bool}> */
    public function fulfillmentTimeline(): array
    {
        $steps = [
            ['status' => 'pending', 'label' => 'Chờ xác nhận', 'description' => 'Clare đã ghi nhận đơn hàng của bạn.', 'at' => $this->placed_at],
            ['status' => 'confirmed', 'label' => 'Chờ lấy hàng', 'description' => 'Đơn đã được xác nhận và đang chờ bàn giao vận chuyển.', 'at' => $this->confirmed_at],
            ['status' => 'processing', 'label' => 'Đang chuẩn bị giao', 'description' => 'Đơn đang được đóng gói để sẵn sàng giao đi.', 'at' => $this->preparing_at],
            ['status' => 'shipped', 'label' => 'Đang giao hàng', 'description' => 'Đơn đã được bàn giao và đang trên đường đến bạn.', 'at' => $this->shipped_at],
            ['status' => 'completed', 'label' => 'Đã giao', 'description' => 'Đơn đã được ghi nhận giao thành công.', 'at' => $this->delivered_at],
        ];
        $rankByStatus = array_flip(array_column($steps, 'status'));
        $reachedRank = $rankByStatus[$this->status] ?? $this->lastReachedFulfillmentRank();

        $timeline = array_map(function (array $step, int $index) use ($reachedRank): array {
            return [
                ...$step,
                'complete' => $index < $reachedRank || ($this->status === 'cancelled' && $index <= $reachedRank),
                'current' => $this->status !== 'cancelled' && $index === $reachedRank,
            ];
        }, $steps, array_keys($steps));

        if ($this->status === 'cancelled') {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Đã hủy',
                'description' => $this->cancel_reason ?: 'Đơn hàng đã được hủy.',
                'at' => $this->cancelled_at,
                'complete' => false,
                'current' => true,
            ];
        }

        return $timeline;
    }

    private function lastReachedFulfillmentRank(): int
    {
        foreach ([
            'delivered_at' => 4,
            'shipped_at' => 3,
            'preparing_at' => 2,
            'confirmed_at' => 1,
        ] as $attribute => $rank) {
            if ($this->{$attribute} !== null) {
                return $rank;
            }
        }

        return 0;
    }
}
