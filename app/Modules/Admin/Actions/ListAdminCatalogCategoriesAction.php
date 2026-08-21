<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Collection;

class ListAdminCatalogCategoriesAction
{
    /** @return Collection<int, Category> */
    public function execute(): Collection
    {
        $categories = Category::query()
            ->with(['parent'])
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->flatten($categories);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    private function flatten(Collection $categories, ?int $parentId = null, int $depth = 0): Collection
    {
        return $categories
            ->where('parent_id', $parentId)
            ->flatMap(function (Category $category) use ($categories, $depth): array {
                $category->setAttribute('tree_depth', $depth);

                return [
                    $category,
                    ...$this->flatten($categories, $category->getKey(), $depth + 1)->all(),
                ];
            })
            ->values();
    }
}
