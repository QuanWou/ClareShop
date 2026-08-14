<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Http\Requests\Admin\Concerns\HasCatalogProductRules;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogVariantRequest extends FormRequest
{
    use HasCatalogProductRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ProductVariant $variant */
        $variant = $this->route('variant');

        return [
            ...$this->variantRules(),
            'sku' => ['required', 'string', 'max:100', Rule::unique(ProductVariant::class, 'sku')->ignore($variant)],
        ];
    }
}
