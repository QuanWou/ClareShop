<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class UpdateCatalogVariantAction
{
    public function execute(ProductVariant $variant, array $validated): ProductVariant
    {
        if (ProductVariant::withTrashed()
            ->where('product_id', $variant->product_id)
            ->where('color_name', $validated['color_name'])
            ->whereKeyNot($variant->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'color_name' => 'Sản phẩm đã có biến thể với tên màu này.',
            ]);
        }

        $variant->update($validated);

        return $variant->fresh();
    }
}
