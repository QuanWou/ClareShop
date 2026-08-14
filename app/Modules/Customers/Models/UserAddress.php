<?php

namespace App\Modules\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recipient_name',
    'phone',
    'address_line_1',
    'address_line_2',
    'ward',
    'district',
    'city',
    'postal_code',
    'country_code',
    'is_default',
])]
class UserAddress extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
