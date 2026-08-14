<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,completed,cancelled'],
            'scheduled_starts_at' => ['required_if:status,confirmed', 'nullable', 'date'],
            'scheduled_ends_at' => ['nullable', 'date', 'after:scheduled_starts_at'],
            'order_number' => ['nullable', 'string', 'max:32', 'exists:orders,number'],
            'internal_note' => ['required_if:status,cancelled', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái mới.',
            'scheduled_starts_at.required_if' => 'Vui lòng chọn lịch đã xác nhận.',
            'scheduled_ends_at.after' => 'Thời gian kết thúc cần sau thời gian bắt đầu.',
            'order_number.exists' => 'Không tìm thấy mã đơn hàng để liên kết.',
            'internal_note.required_if' => 'Vui lòng ghi lý do hủy yêu cầu.',
        ];
    }
}
