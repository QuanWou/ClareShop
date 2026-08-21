<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductReviewStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,approved,hidden'],
            'moderation_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
