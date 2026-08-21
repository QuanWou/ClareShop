<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListPublishedProductsAction
{
    public function __construct(private readonly ListVisibleCategoryTreeAction $listCategories) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     categories: Collection<int, Category>,
     *     brands: Collection<int, Brand>,
     *     filterAttributes: Collection<int, ProductAttribute>,
     *     products: LengthAwarePaginator,
     *     selectedCategory: ?Category,
     *     totalProductCount: int,
     *     maximumCatalogPrice: float,
     *     filters: array<string, mixed>,
     *     viewMode: string
     * }
     */
    public function execute(array $filters = []): array
    {
        $categories = $this->listCategories->execute();
        $selectedCategory = isset($filters['category'])
            ? $categories->firstWhere('slug', $filters['category'])
            : null;

        if (($filters['category'] ?? null) !== null && $selectedCategory === null) {
            abort(404);
        }

        $brands = Brand::query()
            ->visible()
            ->withCount([
                'products as published_products_count' => fn ($query) => $query->published(),
            ])
            ->get();
        $filterAttributes = ProductAttribute::query()
            ->visible()
            ->where('is_filterable', true)
            ->with([
                'values' => fn ($query) => $query
                    ->whereHas('products', fn ($query) => $query->published())
                    ->withCount([
                        'products as published_products_count' => fn ($query) => $query->published(),
                    ]),
            ])
            ->get()
            ->filter(fn (ProductAttribute $attribute): bool => $attribute->values->isNotEmpty())
            ->values();

        $totalProductCount = Product::query()->published()->count();
        $maximumCatalogPrice = (float) (ProductVariant::query()
            ->active()
            ->whereHas('product', fn ($query) => $query->published())
            ->max('price') ?? 0);

        $salesSubquery = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.status', 'completed')
            ->groupBy('product_variants.product_id')
            ->selectRaw('product_variants.product_id, SUM(order_items.quantity) as sold_count');

        $productsQuery = Product::query()
            ->published()
            ->leftJoinSub($salesSubquery, 'product_sales', 'product_sales.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(product_sales.sold_count, 0) as sold_count');

        $productsQuery
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->approved()])
            ->withAvg(['reviews as approved_reviews_average' => fn ($query) => $query->approved()], 'rating')
            ->when(auth()->check(), fn ($query) => $query->withExists([
                'wishlistedBy as is_wishlisted' => fn ($query) => $query->whereKey(auth()->id()),
            ]));

        $this->applyFilters($productsQuery, $filters, $selectedCategory, $categories);
        $this->applySort($productsQuery, (string) ($filters['sort'] ?? 'newest'));

        $products = $productsQuery
            ->withStorefrontSummary()
            ->with(['category', 'categories', 'brand', 'images'])
            ->paginate(12)
            ->withQueryString();
        $viewMode = ($filters['view'] ?? 'grid') === 'list' ? 'list' : 'grid';

        return compact(
            'categories',
            'brands',
            'filterAttributes',
            'products',
            'selectedCategory',
            'totalProductCount',
            'maximumCatalogPrice',
            'filters',
            'viewMode',
        );
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(
        Builder $query,
        array $filters,
        ?Category $selectedCategory,
        Collection $categories,
    ): void {
        if ($selectedCategory !== null) {
            $categoryIds = $this->descendantAndSelfIds($selectedCategory, $categories);
            $query->where(function ($query) use ($categoryIds): void {
                $query
                    ->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn ($query) => $query->whereKey($categoryIds));
            });
        }

        if ($brandSlug = $filters['brand'] ?? null) {
            $query->whereHas('brand', fn ($query) => $query->where('slug', $brandSlug)->where('is_active', true));
        }

        $minimumPrice = $filters['min_price'] ?? null;
        $maximumPrice = $filters['max_price'] ?? null;

        if ($minimumPrice !== null || $maximumPrice !== null) {
            $query->whereHas('activeVariants', function ($query) use ($minimumPrice, $maximumPrice): void {
                $query
                    ->when($minimumPrice !== null, fn ($query) => $query->where('price', '>=', $minimumPrice))
                    ->when($maximumPrice !== null, fn ($query) => $query->where('price', '<=', $maximumPrice));
            });
        }

        foreach (($filters['attributes'] ?? []) as $attributeSlug => $valueSlugs) {
            $valueSlugs = array_values(array_filter((array) $valueSlugs));

            if ($valueSlugs === []) {
                continue;
            }

            $query->whereHas('attributeValues', function ($query) use ($attributeSlug, $valueSlugs): void {
                $query
                    ->whereIn('product_attribute_values.slug', $valueSlugs)
                    ->whereHas('attribute', fn ($query) => $query
                        ->where('slug', $attributeSlug)
                        ->where('is_active', true));
            });
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('minimum_price')->orderByDesc('published_at'),
            'price_desc' => $query->orderByDesc('minimum_price')->orderByDesc('published_at'),
            'bestselling' => $query->orderByDesc('sold_count')->orderByDesc('is_featured')->orderByDesc('published_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };
    }

    /** @return array<int, int> */
    private function descendantAndSelfIds(Category $category, Collection $categories): array
    {
        $ids = collect([$category->getKey()]);

        do {
            $children = $categories
                ->whereIn('parent_id', $ids->all())
                ->pluck('id')
                ->diff($ids);
            $ids = $ids->merge($children)->unique();
        } while ($children->isNotEmpty());

        return $ids->map(fn ($id): int => (int) $id)->values()->all();
    }
}
