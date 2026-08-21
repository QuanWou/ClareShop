<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ListVisibleCategoryTreeAction
{
    /** @var Collection<int, Category>|null */
    private ?Collection $cached = null;

    /** @return Collection<int, Category> */
    public function execute(): Collection
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        if (! Schema::hasTable('categories')) {
            return $this->cached = collect();
        }

        $categories = Category::query()->visible()->get();
        $products = Product::query()->published()->select(['id', 'category_id'])->with('categories:id')->get();

        foreach ($categories as $category) {
            $categoryIds = $this->descendantAndSelfIds($category, $categories);
            $category->setAttribute('published_products_count', $products->filter(fn (Product $product): bool => in_array($product->category_id, $categoryIds, true)
                || $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty())->count());
        }

        return $this->cached = $this->flatten($categories);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    private function flatten(Collection $categories, ?int $parentId = null, int $depth = 0): Collection
    {
        $knownIds = $categories->modelKeys();

        return $categories
            ->filter(function (Category $category) use ($parentId, $knownIds): bool {
                $effectiveParentId = in_array($category->parent_id, $knownIds, true)
                    ? $category->parent_id
                    : null;

                return $effectiveParentId === $parentId;
            })
            ->flatMap(function (Category $category) use ($categories, $depth): array {
                $category->setAttribute('tree_depth', $depth);

                return [
                    $category,
                    ...$this->flatten($categories, $category->getKey(), $depth + 1)->all(),
                ];
            })
            ->values();
    }

    /** @return array<int, int> */
    private function descendantAndSelfIds(Category $category, Collection $categories): array
    {
        $ids = collect([(int) $category->getKey()]);

        do {
            $children = $categories->whereIn('parent_id', $ids)->pluck('id')->map(fn ($id): int => (int) $id)->diff($ids);
            $ids = $ids->merge($children)->unique();
        } while ($children->isNotEmpty());

        return $ids->values()->all();
    }
}
