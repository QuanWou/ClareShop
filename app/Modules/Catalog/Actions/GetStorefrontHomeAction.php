<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class GetStorefrontHomeAction
{
    public function __construct(private readonly ListVisibleCategoryTreeAction $listCategories) {}

    public function execute(): array
    {
        $categories = $this->listCategories->execute()
            ->where('published_products_count', '>', 0)
            ->take(8)
            ->values();

        $featuredProducts = Product::query()
            ->published()
            ->featured()
            ->withStorefrontSummary()
            ->with(['category', 'categories', 'brand', 'images'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return compact('categories', 'featuredProducts');
    }
}
