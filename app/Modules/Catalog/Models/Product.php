<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'material',
        'dimensions',
        'is_active',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('activeVariants');
    }

    public function isPublished(): bool
    {
        return $this->is_active
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithStorefrontSummary(Builder $query): Builder
    {
        return $query
            ->withMin('activeVariants as minimum_price', 'price')
            ->withMax('activeVariants as maximum_price', 'price')
            ->withExists([
                'activeVariants as has_in_stock_variants' => fn (Builder $query) => $query
                    ->where('stock_quantity', '>', 0),
            ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function archivedVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->onlyTrashed()
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
