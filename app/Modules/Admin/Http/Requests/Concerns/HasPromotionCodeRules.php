<?php

namespace App\Modules\Admin\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait HasPromotionCodeRules
{
    protected function promotionCodeRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:1'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'Mức giảm theo phần trăm không được vượt quá 100%.');
            }
        });
    }

    protected function preparePromotionCodeForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    protected function promotionCodeMessages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã ưu đãi.',
            'code.regex' => 'Mã chỉ dùng chữ in hoa, số và dấu gạch ngang.',
            'name.required' => 'Vui lòng nhập tên chương trình.',
            'discount_type.required' => 'Vui lòng chọn kiểu giảm giá.',
            'discount_value.required' => 'Vui lòng nhập mức giảm.',
            'ends_at.after_or_equal' => 'Thời điểm kết thúc phải sau thời điểm bắt đầu.',
        ];
    }
}
