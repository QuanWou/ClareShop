<?php

namespace App\Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectCartItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'cart_item_ids.required' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
            'cart_item_ids.min' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
            'cart_item_ids.*.integer' => 'Sản phẩm được chọn không hợp lệ.',
            'cart_item_ids.*.distinct' => 'Danh sách sản phẩm được chọn đang bị trùng.',
        ];
    }
}
