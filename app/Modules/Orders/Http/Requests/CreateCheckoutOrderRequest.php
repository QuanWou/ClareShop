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
            'pay_later_term_months' => ['exclude_unless:payment_method,pay_later', 'required_if:payment_method,pay_later', 'integer', 'in:2,4'],
            'pay_later_confirm' => ['exclude_unless:payment_method,pay_later', 'accepted_if:payment_method,pay_later'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'pay_later_term_months.required_if' => 'Vui lòng chọn thời hạn thanh toán sau 2 hoặc 4 tháng.',
            'pay_later_term_months.in' => 'Thời hạn trả sau chỉ có thể là 2 hoặc 4 tháng.',
            'pay_later_confirm.accepted_if' => 'Bạn cần xác nhận đã hiểu nghĩa vụ thanh toán trước khi đặt đơn.',
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
