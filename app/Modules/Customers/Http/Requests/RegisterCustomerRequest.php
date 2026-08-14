<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $this->input('name')));
        $phone = preg_replace('/[\s.\-()]+/', '', trim((string) $this->input('phone')));

        $this->merge([
            'name' => $name,
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => filled($phone) ? $phone : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^(?:\+84|0)(?:3|5|7|8|9)\d{8}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ và tên cần có ít nhất 2 ký tự.',
            'name.max' => 'Họ và tên không được dài quá 80 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.max' => 'Email không được dài quá 255 ký tự.',
            'email.unique' => 'Email này đã có tài khoản.',
            'phone.regex' => 'Số điện thoại Việt Nam chưa đúng định dạng.',
            'password.required' => 'Vui lòng tạo mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu chưa khớp.',
            'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu cần có ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu cần có ít nhất một chữ số.',
        ];
    }
}
