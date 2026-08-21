<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Support\Str;

class CreateCatalogAttributeAction
{
    public function execute(array $validated): ProductAttribute
    {
        $validated['slug'] = $this->uniqueSlug(($validated['slug'] ?? null) ?: $validated['name']);

        return ProductAttribute::query()->create($validated);
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'thuoc-tinh';
        $slug = $base;
        $suffix = 2;

        while (ProductAttribute::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
