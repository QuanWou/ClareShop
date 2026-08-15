<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('name')) {
            $normalized['name'] = preg_replace('/\s+/u', ' ', trim((string) $this->input('name')));
        }

        if ($this->has('email')) {
            $normalized['email'] = strtolower(trim((string) $this->input('email')));
        }

        if ($this->has('phone')) {
            $phone = preg_replace('/[\s.\-()]+/', '', trim((string) $this->input('phone')));
            $normalized['phone'] = filled($phone) ? $phone : null;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:80'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^(?:\+84|0)(?:3|5|7|8|9)\d{8}$/'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'in:customer,admin'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
