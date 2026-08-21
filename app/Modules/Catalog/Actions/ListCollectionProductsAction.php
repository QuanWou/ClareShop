<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;

class ListCollectionProductsAction
{
    public function __construct(private readonly ListPublishedProductsAction $listProducts) {}

    /** @param array<string, mixed> $filters */
    public function execute(Category $category, array $filters = []): array
    {
        $data = $this->listProducts->execute([
            ...$filters,
            'category' => $category->slug,
        ]);
        $data['category'] = $data['selectedCategory'];

        return $data;
    }
}
