<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClarePayWallet extends Model
{
    protected $fillable = ['user_id', 'balance'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ClarePayTransaction::class, 'wallet_id');
    }
}
