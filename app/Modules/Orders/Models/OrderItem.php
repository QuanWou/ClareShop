<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'product_name',
        'product_slug',
        'color_name',
        'sku',
        'image_path',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    public function imageUrl(): ?string
    {
        if (filled($this->image_path)) {
            if (Str::startsWith($this->image_path, ['http://', 'https://'])) {
                return $this->image_path;
            }

            $path = ltrim($this->image_path, '/');

            return Storage::disk('public')->exists($path)
                ? '/storage/'.$path
                : (is_file(public_path($path)) ? '/'.$path : '/images/catalog/product-placeholder.svg');
        }

        return $this->variant?->images->first()?->url
            ?? $this->variant?->product?->images->first()?->url;
    }
}
