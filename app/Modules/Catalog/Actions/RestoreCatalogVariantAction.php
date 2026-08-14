<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class RestoreCatalogVariantAction
{
    public function execute(ProductVariant $variant): void
    {
        if (! $variant->trashed()) {
            throw ValidationException::withMessages([
                'variant' => 'Biến thể này chưa được lưu trữ.',
            ]);
        }

        $variant->restore();
    }
}
