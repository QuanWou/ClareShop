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
            $productData = Arr::except($validated, ['initial_variant']);
            $productData['slug'] = $this->resolveSlug->execute(($productData['slug'] ?? null) ?: $productData['name'], Product::class);
            $product = Product::query()->create($productData);
            $product->variants()->create($validated['initial_variant']);

            return $product->fresh(['category', 'variants', 'images']);
        });
    }
}
