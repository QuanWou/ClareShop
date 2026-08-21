<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCatalogProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category' => trim((string) $this->input('category')) ?: null,
            'brand' => trim((string) $this->input('brand')) ?: null,
            'sort' => trim((string) $this->input('sort')) ?: 'newest',
            'view' => trim((string) $this->input('view')) ?: 'grid',
        ]);
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120', 'exists:brands,slug'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price', 'max:999999999'],
            'sort' => ['required', 'in:newest,bestselling,price_asc,price_desc'],
            'view' => ['required', 'in:grid,list'],
            'attributes' => ['nullable', 'array', 'max:30'],
            'attributes.*' => ['nullable', 'array', 'max:30'],
            'attributes.*.*' => ['string', 'max:120'],
        ];
    }
}
