<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,confirmed,processing,shipped,completed,cancelled'],
            'payment_status' => ['nullable', 'in:unpaid,pending,paid,refunded'],
            'q' => ['nullable', 'string', 'max:80'],
        ];
    }
}
