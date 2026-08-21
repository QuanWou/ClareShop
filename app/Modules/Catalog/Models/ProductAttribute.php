<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'unit',
        'filter_type',
        'is_filterable',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)
            ->orderBy('sort_order')
            ->orderBy('label');
    }
}
