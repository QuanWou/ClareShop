<?php

namespace App\Modules\Orders\Http\Requests;

class CreateCheckoutOrderRequest extends QuoteCheckoutRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'customer_name.required' => 'Vui lòng nhập họ tên đặt hàng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại liên hệ.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
