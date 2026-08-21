<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Support\Collection;

class ListAdminCatalogBrandsAction
{
    /** @return Collection<int, Brand> */
    public function execute(): Collection
    {
        return Brand::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
