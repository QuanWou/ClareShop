<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim((string) $this->input('q')),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ];
    }
}
