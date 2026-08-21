<?php

namespace App\Modules\Catalog\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Validator;

trait HasCatalogProductRules
{
    protected function productRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array', 'max:20'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'attribute_value_ids' => ['nullable', 'array', 'max:50'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:product_attribute_values,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'material' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function variantRules(string $prefix = ''): array
    {
        return [
            $prefix.'sku' => ['required', 'string', 'max:100'],
            $prefix.'color_name' => ['required', 'string', 'max:100'],
            $prefix.'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            $prefix.'price' => ['required', 'numeric', 'min:1'],
            $prefix.'compare_at_price' => ['nullable', 'numeric', 'min:1'],
            $prefix.'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            $prefix.'weight_grams' => ['required', 'integer', 'min:1', 'max:1000000'],
            $prefix.'is_active' => ['required', 'boolean'],
            $prefix.'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $variant = $this->input('initial_variant', $this->all());
            $price = $variant['price'] ?? null;
            $compareAtPrice = $variant['compare_at_price'] ?? null;

            if ($price !== null && $compareAtPrice !== null && (float) $compareAtPrice <= (float) $price) {
                $field = $this->has('initial_variant') ? 'initial_variant.compare_at_price' : 'compare_at_price';
                $validator->errors()->add($field, 'Giá tham chiếu phải lớn hơn giá bán.');
            }
        });
    }
}
