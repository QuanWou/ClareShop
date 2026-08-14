<?php

namespace App\Modules\Appointments\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'number',
        'user_id',
        'order_id',
        'type',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'preferred_starts_at',
        'preferred_ends_at',
        'scheduled_starts_at',
        'scheduled_ends_at',
        'address_line_1',
        'address_line_2',
        'ward',
        'district',
        'city',
        'country_code',
        'customer_note',
        'internal_note',
        'confirmed_by',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_starts_at' => 'datetime',
            'preferred_ends_at' => 'datetime',
            'scheduled_starts_at' => 'datetime',
            'scheduled_ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class);
    }

    public function typeLabel(): string
    {
        return $this->type === 'installation' ? 'Lắp đặt' : 'Tư vấn chọn đèn';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            default => $this->status,
        };
    }
}
