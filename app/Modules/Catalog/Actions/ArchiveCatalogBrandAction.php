<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Brand;

class ArchiveCatalogBrandAction
{
    public function execute(Brand $brand): void
    {
        $brand->delete();
    }
}
