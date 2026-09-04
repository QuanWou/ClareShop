<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'disk',
        'path',
        'alt_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::get(function (): string {
            $path = ltrim((string) $this->path, '/');
            $fallback = '/images/catalog/product-placeholder.svg';

            if ($this->disk === 'asset') {
                return is_file(public_path($path)) ? '/'.$path : $fallback;
            }

            if ($this->disk === 'public') {
                return Storage::disk('public')->exists($path) ? '/storage/'.$path : $fallback;
            }

            return Storage::disk($this->disk)->exists($path)
                ? Storage::disk($this->disk)->url($path)
                : $fallback;
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
