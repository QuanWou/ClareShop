<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreManagedUserRequest extends FormRequest
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
            'role' => ['required', 'in:customer,admin'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
