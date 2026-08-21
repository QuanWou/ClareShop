<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Support\Str;

class UpdateCatalogAttributeValueAction
{
    public function execute(ProductAttributeValue $value, array $validated): ProductAttributeValue
    {
        $base = Str::slug(($validated['slug'] ?? null) ?: $validated['label']) ?: 'gia-tri';
        $slug = $base;
        $suffix = 2;

        while (ProductAttributeValue::query()
            ->where('product_attribute_id', $value->product_attribute_id)
            ->where('slug', $slug)
            ->whereKeyNot($value->getKey())
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $validated['slug'] = $slug;
        $value->update($validated);

        return $value->fresh();
    }
}
