<?php

namespace App\Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'widget_enabled' => ['sometimes', 'boolean'],
            'assistant_name' => ['required', 'string', 'max:80'],
            'welcome_message' => ['required', 'string', 'max:500'],
            'handoff_message' => ['required', 'string', 'max:500'],
            'gemini_unavailable_message' => ['required', 'string', 'max:500'],
            'guest_order_message' => ['required', 'string', 'max:500'],
            'closed_message' => ['required', 'string', 'max:500'],
            'admin_unavailable_message' => ['required', 'string', 'max:500'],
            'gemini_enabled' => ['sometimes', 'boolean'],
            'external_questions_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'widget_enabled' => $this->boolean('widget_enabled'),
            'gemini_enabled' => $this->boolean('gemini_enabled'),
            'external_questions_enabled' => $this->boolean('external_questions_enabled'),
        ]);
    }
}
