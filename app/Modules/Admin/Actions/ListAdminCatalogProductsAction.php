<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminCatalogProductsAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Product::query()
            ->when(
                ($filters['status'] ?? null) === 'archived',
                fn ($query) => $query->onlyTrashed(),
            )
            ->with(['category', 'images'])
            ->withCount(['variants', 'activeVariants', 'images'])
            ->withSum('variants as total_stock', 'stock_quantity')
            ->withMin('variants as minimum_price', 'price')
            ->withMax('variants as maximum_price', 'price')
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();
    }
}
