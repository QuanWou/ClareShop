<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Support\Collection;

class ListAdminCatalogAttributesAction
{
    /** @return Collection<int, ProductAttribute> */
    public function execute(): Collection
    {
        return ProductAttribute::query()
            ->withCount('values')
            ->with(['values' => fn ($query) => $query->withCount('products')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
