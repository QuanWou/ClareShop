<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class CreateCatalogVariantAction
{
    public function execute(Product $product, array $validated): ProductVariant
    {
        if ($product->variants()->withTrashed()->where('color_name', $validated['color_name'])->exists()) {
            throw ValidationException::withMessages([
                'color_name' => 'Sản phẩm đã có biến thể với tên màu này.',
            ]);
        }

        return $product->variants()->create($validated);
    }
}
