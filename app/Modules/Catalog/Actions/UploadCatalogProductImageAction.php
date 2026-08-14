<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadCatalogProductImageAction
{
    public function execute(Product $product, array $validated): ProductImage
    {
        $variantId = $validated['product_variant_id'] ?? null;

        if ($variantId !== null && ! $product->variants()->withTrashed()->whereKey($variantId)->exists()) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Biến thể được chọn không thuộc sản phẩm này.',
            ]);
        }

        /** @var UploadedFile $image */
        $image = $validated['image'];

        return $product->images()->create([
            'product_variant_id' => $variantId,
            'disk' => 'public',
            'path' => $image->store('catalog/products', 'public'),
            'alt_text' => $validated['alt_text'] ?? null,
            'sort_order' => $validated['sort_order'],
        ]);
    }
}
