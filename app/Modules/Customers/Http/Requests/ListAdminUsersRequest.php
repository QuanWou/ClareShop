<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:customer,admin'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
