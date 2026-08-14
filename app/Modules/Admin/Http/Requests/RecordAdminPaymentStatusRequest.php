<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordAdminPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', 'in:paid,refunded'],
            'payment_note' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => 'Vui lòng chọn trạng thái thanh toán.',
            'payment_note.required' => 'Vui lòng ghi chú đối soát hoặc mã hoàn tiền.',
            'payment_note.min' => 'Ghi chú thanh toán cần có ít nhất 3 ký tự.',
        ];
    }
}
