<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Http\Requests\Concerns\HasShippingAddressRules;
use Illuminate\Foundation\Http\FormRequest;

class QuoteCheckoutRequest extends FormRequest
{
    use HasShippingAddressRules;

    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            ...$this->shippingAddressRules(),
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
