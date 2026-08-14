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
        ]);
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:120'],
        ];
    }
}
