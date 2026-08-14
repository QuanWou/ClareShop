<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class ArchiveCatalogProductAction
{
    public function execute(Product $product): void
    {
        $product->delete();
    }
}
