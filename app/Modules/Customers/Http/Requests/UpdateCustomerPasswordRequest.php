<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'password_current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password_current_password.current_password' => 'Mật khẩu hiện tại chưa chính xác.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới chưa khớp.',
            'password.min' => 'Mật khẩu mới cần có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu mới cần có ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu mới cần có ít nhất một chữ số.',
        ];
    }
}
