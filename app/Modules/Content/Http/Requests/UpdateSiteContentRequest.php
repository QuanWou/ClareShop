<?php

namespace App\Modules\Content\Http\Requests;

use App\Modules\Content\Support\SiteContentRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'content' => ['required', 'array'],
            'images' => ['nullable', 'array'],
        ];

        foreach (app(SiteContentRegistry::class)->definitions() as $key => $definition) {
            if ($definition['type'] === 'image') {
                $rules['images.'.$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

                continue;
            }

            $fieldRules = ['required', 'string', 'max:'.($definition['max'] ?? 500)];

            if ($definition['type'] === 'email') {
                $fieldRules[] = 'email:rfc';
            }

            $rules['content.'.$key] = $fieldRules;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'content.*.required' => 'Vui lòng không để trống nội dung.',
            'content.*.max' => 'Một nội dung đang dài hơn giới hạn cho phép.',
            'content.global_contact_email.email' => 'Email liên hệ chưa đúng định dạng.',
            'images.*.image' => 'Tệp tải lên phải là ảnh.',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'images.*.max' => 'Ảnh không được lớn hơn 5 MB.',
        ];
    }
}
