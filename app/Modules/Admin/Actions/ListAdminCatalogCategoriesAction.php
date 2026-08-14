<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Collection;

class ListAdminCatalogCategoriesAction
{
    /** @return Collection<int, Category> */
    public function execute(): Collection
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
