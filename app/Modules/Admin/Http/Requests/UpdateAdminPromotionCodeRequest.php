<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Admin\Http\Requests\Concerns\HasPromotionCodeRules;
use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminPromotionCodeRequest extends FormRequest
{
    use HasPromotionCodeRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePromotionCodeForValidation();
    }

    public function rules(): array
    {
        /** @var PromotionCode $promotion */
        $promotion = $this->route('promotion');

        return [
            ...$this->promotionCodeRules(),
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique(PromotionCode::class, 'code')->ignore($promotion),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->promotionCodeMessages(),
            'code.unique' => 'Mã ưu đãi này đã tồn tại.',
        ];
    }
}
