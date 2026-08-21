<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateCatalogProductAction
{
    public function __construct(private readonly ResolveUniqueCatalogSlugAction $resolveSlug) {}

    public function execute(array $validated): Product
    {
        return DB::transaction(function () use ($validated): Product {
            $categoryIds = collect($validated['category_ids'] ?? [])
                ->push($validated['category_id'] ?? null)
                ->filter()
                ->unique()
                ->values();
            $attributeValueIds = collect($validated['attribute_value_ids'] ?? [])->unique()->values();
            $productData = Arr::except($validated, ['initial_variant', 'category_ids', 'attribute_value_ids']);
            $productData['category_id'] = $validated['category_id'] ?? $categoryIds->first();
            $productData['slug'] = $this->resolveSlug->execute(($productData['slug'] ?? null) ?: $productData['name'], Product::class);
            $product = Product::query()->create($productData);
            $product->variants()->create($validated['initial_variant']);
            $product->categories()->sync($categoryIds->all());
            $product->attributeValues()->sync($attributeValueIds->all());

            return $product->fresh(['category', 'categories', 'brand', 'attributeValues.attribute', 'variants', 'images']);
        });
    }
}
