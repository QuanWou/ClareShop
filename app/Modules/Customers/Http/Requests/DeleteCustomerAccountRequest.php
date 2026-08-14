<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCustomerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deletion_current_password' => ['required', 'string', 'current_password'],
            'confirmation' => ['required', 'string', 'in:XOA TAI KHOAN'],
        ];
    }

    public function messages(): array
    {
        return [
            'deletion_current_password.required' => 'Vui lòng nhập mật khẩu hiện tại để xác nhận.',
            'deletion_current_password.current_password' => 'Mật khẩu hiện tại chưa chính xác.',
            'confirmation.required' => 'Vui lòng nhập XOA TAI KHOAN để xác nhận.',
            'confirmation.in' => 'Nội dung xác nhận chưa đúng. Hãy nhập XOA TAI KHOAN.',
        ];
    }
}
