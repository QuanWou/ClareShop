<?php

namespace App\Modules\Appointments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:consultation,installation'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'preferred_starts_at' => ['required', 'date', 'after:now'],
            'preferred_ends_at' => ['nullable', 'date', 'after:preferred_starts_at'],
            'address_line_1' => ['required_if:type,installation', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'ward' => ['required_if:type,installation', 'nullable', 'string', 'max:255'],
            'district' => ['required_if:type,installation', 'nullable', 'string', 'max:255'],
            'city' => ['required_if:type,installation', 'nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn nhu cầu hỗ trợ.',
            'type.in' => 'Nhu cầu hỗ trợ không hợp lệ.',
            'customer_name.required' => 'Vui lòng nhập họ và tên.',
            'customer_email.required' => 'Vui lòng nhập email.',
            'customer_email.email' => 'Email chưa đúng định dạng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'preferred_starts_at.required' => 'Vui lòng chọn thời gian mong muốn.',
            'preferred_starts_at.after' => 'Thời gian mong muốn cần ở trong tương lai.',
            'preferred_ends_at.after' => 'Thời gian kết thúc cần sau thời gian bắt đầu.',
            'address_line_1.required_if' => 'Vui lòng nhập địa chỉ để yêu cầu lắp đặt.',
            'ward.required_if' => 'Vui lòng nhập phường / xã để yêu cầu lắp đặt.',
            'district.required_if' => 'Vui lòng nhập quận / huyện để yêu cầu lắp đặt.',
            'city.required_if' => 'Vui lòng nhập tỉnh / thành phố để yêu cầu lắp đặt.',
        ];
    }
}
