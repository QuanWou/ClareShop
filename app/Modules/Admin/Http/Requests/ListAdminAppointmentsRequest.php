<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,confirmed,completed,cancelled'],
            'type' => ['nullable', 'in:consultation,installation'],
            'q' => ['nullable', 'string', 'max:80'],
        ];
    }
}
