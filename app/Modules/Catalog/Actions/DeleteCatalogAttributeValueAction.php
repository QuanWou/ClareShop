<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Validation\ValidationException;

class DeleteCatalogAttributeValueAction
{
    public function execute(ProductAttributeValue $value): void
    {
        if ($value->products()->exists()) {
            throw ValidationException::withMessages([
                'attribute_value' => 'Giá trị đang được gắn với sản phẩm. Hãy gỡ khỏi sản phẩm trước khi xóa.',
            ]);
        }

        $value->delete();
    }
}
