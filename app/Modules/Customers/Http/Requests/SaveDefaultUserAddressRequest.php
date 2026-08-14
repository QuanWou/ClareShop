<?php

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDefaultUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s.\-()]+/', '', trim((string) $this->input('address_phone')));

        $this->merge([
            'address_phone' => $phone,
            'country_code' => strtoupper(trim((string) $this->input('country_code', 'VN'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'min:2', 'max:255'],
            'address_phone' => ['required', 'string', 'regex:/^(?:\+84|0)(?:3|5|7|8|9)\d{8}$/'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'ward' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2', 'in:VN'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'recipient_name.min' => 'Tên người nhận cần có ít nhất 2 ký tự.',
            'address_phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'address_phone.regex' => 'Số điện thoại Việt Nam chưa đúng định dạng.',
            'address_line_1.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'ward.required' => 'Vui lòng nhập phường / xã.',
            'district.required' => 'Vui lòng nhập quận / huyện.',
            'city.required' => 'Vui lòng nhập tỉnh / thành phố.',
            'country_code.in' => 'Hiện Clare chỉ hỗ trợ giao hàng tại Việt Nam.',
        ];
    }
}
