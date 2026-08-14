<?php

namespace App\Modules\Orders\Http\Requests\Concerns;

trait HasShippingAddressRules
{
    protected function shippingAddressRules(): array
    {
        return [
            'shipping_recipient_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'shipping_address_line_1' => ['required', 'string', 'max:255'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_ward' => ['required', 'string', 'max:255'],
            'shipping_district' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country_code' => ['required', 'string', 'size:2', 'in:VN'],
        ];
    }

    protected function shippingAddressMessages(): array
    {
        return [
            'shipping_recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'shipping_phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'shipping_address_line_1.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'shipping_ward.required' => 'Vui lòng nhập phường/xã.',
            'shipping_district.required' => 'Vui lòng nhập quận/huyện.',
            'shipping_city.required' => 'Vui lòng nhập tỉnh/thành phố.',
            'shipping_country_code.in' => 'Hiện Clare chỉ hỗ trợ giao hàng tại Việt Nam.',
        ];
    }
}
