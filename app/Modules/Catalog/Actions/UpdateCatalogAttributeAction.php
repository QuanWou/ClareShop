<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Support\Str;

class UpdateCatalogAttributeAction
{
    public function execute(ProductAttribute $attribute, array $validated): ProductAttribute
    {
        $base = Str::slug(($validated['slug'] ?? null) ?: $validated['name']) ?: 'thuoc-tinh';
        $slug = $base;
        $suffix = 2;

        while (ProductAttribute::query()->where('slug', $slug)->whereKeyNot($attribute->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $validated['slug'] = $slug;
        $attribute->update($validated);

        return $attribute->fresh('values');
    }
}
