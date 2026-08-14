<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\Product;

class ShowAdminCatalogProductAction
{
    public function execute(Product $product): Product
    {
        return $product->load([
            'category',
            'variants.images',
            'archivedVariants',
            'images.variant',
        ]);
    }
}
