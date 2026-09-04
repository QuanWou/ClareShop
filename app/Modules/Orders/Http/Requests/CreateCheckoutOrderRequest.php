<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Validation\Validator;

class CreateCheckoutOrderRequest extends QuoteCheckoutRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'payment_method' => ['required', 'string', 'in:'.implode(',', PaymentMethodCatalog::codes())],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('payment_method') === 'bank_transfer' && ! config('services.payos.enabled')) {
                $validator->errors()->add(
                    'payment_method',
                    'payOS chưa được cấu hình đầy đủ. Vui lòng chọn phương thức khác hoặc bổ sung ba khóa payOS trong .env.',
                );
            }
        }];
    }
}
