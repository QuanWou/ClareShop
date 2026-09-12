<?php

namespace App\Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1200'],
            'client_message_id' => ['required', 'uuid'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
