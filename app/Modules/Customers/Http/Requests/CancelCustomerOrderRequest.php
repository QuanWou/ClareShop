<?php

namespace App\Modules\Customers\Http\Requests;

use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelCustomerOrderRequest extends FormRequest
{
    private const REASONS = [
        'Tôi gặp vấn đề khi thanh toán',
        'Tôi muốn thay đổi sản phẩm hoặc số lượng',
        'Thời gian giao hàng không phù hợp',
        'Tôi không còn nhu cầu mua hàng',
        'Lý do khác',
    ];

    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user() !== null
            && (int) $order->user_id === (int) $this->user()->getAuthIdentifier();
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', Rule::in(self::REASONS)],
            'cancel_note' => ['nullable', 'required_if:cancel_reason,Lý do khác', 'string', 'max:500'],
            'confirm_cancel' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Vui lòng chọn lý do hủy đơn.',
            'cancel_reason.in' => 'Lý do hủy đơn không hợp lệ.',
            'cancel_note.required_if' => 'Vui lòng ghi rõ lý do hủy đơn.',
            'cancel_note.max' => 'Nội dung bổ sung không được vượt quá 500 ký tự.',
            'confirm_cancel.accepted' => 'Bạn cần xác nhận đã hiểu đơn sẽ bị hủy và không thể khôi phục.',
        ];
    }

    public function cancellationReason(): string
    {
        $reason = (string) $this->validated('cancel_reason');
        $note = trim((string) $this->validated('cancel_note', ''));

        return $note === '' ? $reason : $reason.': '.$note;
    }
}
