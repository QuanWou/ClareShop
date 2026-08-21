<?php

namespace App\Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:80'],
            'store_email' => ['required', 'email', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:500'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:1024'],
            'appearance_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'seo_default_title' => ['required', 'string', 'max:70'],
            'seo_default_description' => ['required', 'string', 'max:170'],
            'google_client_id' => ['nullable', 'string', 'max:500'],
            'google_client_secret' => ['nullable', 'string', 'max:1000'],
            'google_redirect_url' => ['nullable', 'url:http,https', 'max:500'],
            'facebook_client_id' => ['nullable', 'string', 'max:500'],
            'facebook_client_secret' => ['nullable', 'string', 'max:1000'],
            'facebook_redirect_url' => ['nullable', 'url:http,https', 'max:500'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:1000'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
