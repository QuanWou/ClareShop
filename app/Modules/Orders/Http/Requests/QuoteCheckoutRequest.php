<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Http\Requests\Concerns\HasShippingAddressRules;
use App\Modules\Orders\Support\ShippingOptionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteCheckoutRequest extends FormRequest
{
    use HasShippingAddressRules;

    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('shipping_option')) {
            $this->merge(['shipping_option' => ShippingOptionCatalog::defaultCode()]);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->shippingAddressRules(),
            'shipping_option' => ['required', 'string', Rule::in(ShippingOptionCatalog::codes())],
            'discount_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->shippingAddressMessages(),
            'discount_code.max' => 'Mã ưu đãi không được vượt quá 50 ký tự.',
        ];
    }
}
