<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Support\Str;

class CreateCatalogAttributeValueAction
{
    public function execute(ProductAttribute $attribute, array $validated): ProductAttributeValue
    {
        $validated['slug'] = $this->uniqueSlug($attribute, ($validated['slug'] ?? null) ?: $validated['label']);

        return $attribute->values()->create($validated);
    }

    private function uniqueSlug(ProductAttribute $attribute, string $source): string
    {
        $base = Str::slug($source) ?: 'gia-tri';
        $slug = $base;
        $suffix = 2;

        while ($attribute->values()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
