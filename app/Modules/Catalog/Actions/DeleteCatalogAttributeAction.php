<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Validation\ValidationException;

class DeleteCatalogAttributeAction
{
    public function execute(ProductAttribute $attribute): void
    {
        if ($attribute->values()->whereHas('products')->exists()) {
            throw ValidationException::withMessages([
                'attribute' => 'Không thể xóa thuộc tính đang được gắn với sản phẩm. Hãy gỡ các giá trị khỏi sản phẩm trước.',
            ]);
        }

        $attribute->delete();
    }
}
