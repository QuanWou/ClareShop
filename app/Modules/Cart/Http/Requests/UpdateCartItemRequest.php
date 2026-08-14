<?php

namespace App\Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('commerce.cart.maximum_quantity')],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            'quantity.min' => 'Số lượng tối thiểu là 1.',
            'quantity.max' => 'Số lượng tối đa là '.config('commerce.cart.maximum_quantity').'.',
        ];
    }
}
