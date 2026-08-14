<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class UpdateCatalogProductAction
{
    public function __construct(private readonly ResolveUniqueCatalogSlugAction $resolveSlug) {}

    public function execute(Product $product, array $validated): Product
    {
        $validated['slug'] = $this->resolveSlug->execute(($validated['slug'] ?? null) ?: $validated['name'], Product::class, $product->getKey());
        $product->update($validated);

        return $product->fresh(['category', 'variants', 'images']);
    }
}
