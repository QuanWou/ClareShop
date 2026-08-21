<?php

namespace App\Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:12'],
            'files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
