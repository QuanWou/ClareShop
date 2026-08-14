<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Http\Requests\Admin\Concerns\HasCatalogProductRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogProductRequest extends FormRequest
{
    use HasCatalogProductRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->productRules();
    }
}
