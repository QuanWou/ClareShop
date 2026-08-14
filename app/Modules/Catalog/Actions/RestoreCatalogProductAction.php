<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use Illuminate\Validation\ValidationException;

class RestoreCatalogProductAction
{
    public function execute(Product $product): void
    {
        if (! $product->trashed()) {
            throw ValidationException::withMessages([
                'product' => 'Sản phẩm này chưa được lưu trữ.',
            ]);
        }

        $product->restore();
    }
}
