<?php

namespace App\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PayLaterPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['paypal', 'clare_pay', 'payos'])],
            'confirm_payment' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('payment_method') === 'payos' && ! config('services.payos.enabled')) {
                $validator->errors()->add('payment_method', 'payOS chưa được cấu hình đầy đủ.');
            }
        }];
    }
}
