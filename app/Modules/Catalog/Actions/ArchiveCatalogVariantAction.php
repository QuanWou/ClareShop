<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductVariant;

class ArchiveCatalogVariantAction
{
    public function execute(ProductVariant $variant): void
    {
        $variant->delete();
    }
}
