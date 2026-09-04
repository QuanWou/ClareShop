<?php

namespace App\Modules\Customers\Http\Requests;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCustomerOrderPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user() !== null
            && (int) $order->user_id === (int) $this->user()->getAuthIdentifier();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(PaymentMethodCatalog::codes())],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
