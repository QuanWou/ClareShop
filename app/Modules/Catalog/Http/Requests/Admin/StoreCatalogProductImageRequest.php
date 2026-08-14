<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Vui lòng chọn tệp ảnh cần tải lên.',
            'image.uploaded' => 'Không thể tải tệp ảnh lên. Ảnh phải không quá 5 MB.',
            'image.image' => 'Tệp đã chọn không phải là ảnh hợp lệ.',
            'image.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh không được lớn hơn 5 MB.',
        ];
    }
}
