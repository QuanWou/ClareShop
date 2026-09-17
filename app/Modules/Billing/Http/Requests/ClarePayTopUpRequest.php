<?php

namespace App\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClarePayTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:10000', 'max:100000000'],
            'confirm_top_up' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! config('services.payos.enabled')) {
                $validator->errors()->add('amount', 'PayOS chưa được cấu hình nên chưa thể nạp Clare Pay.');
            }
        }];
    }
}
