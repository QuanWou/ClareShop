<?php

namespace App\Modules\Settings\Support;

use App\Modules\Settings\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class SiteSettingsRegistry
{
    /** @var array<string, string|null>|null */
    private ?array $values = null;

    public function get(string $key): string
    {
        return (string) ($this->all()[$key] ?? config("site-settings.defaults.{$key}", ''));
    }

    public function configured(string $key): bool
    {
        return trim($this->get($key)) !== '';
    }

    public function socialProviderConfigured(string $provider): bool
    {
        return $this->configured("{$provider}_client_id")
            && $this->configured("{$provider}_client_secret")
            && $this->configured("{$provider}_redirect_url");
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $values = config('site-settings.defaults', []);

        if (! Schema::hasTable('site_settings')) {
            return $this->values = $values;
        }

        foreach (SiteSetting::query()->get() as $setting) {
            try {
                $values[$setting->key] = $setting->is_secret && $setting->value !== null
                    ? Crypt::decryptString($setting->value)
                    : $setting->value;
            } catch (\Throwable) {
                $values[$setting->key] = '';
            }
        }

        return $this->values = $values;
    }

    public function clear(): void
    {
        $this->values = null;
    }
}
