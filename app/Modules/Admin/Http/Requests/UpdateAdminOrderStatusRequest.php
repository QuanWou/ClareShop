<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,processing,shipped,completed,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái mới cho đơn.',
            'cancel_reason.required_if' => 'Vui lòng ghi rõ lý do hủy đơn.',
        ];
    }
}
