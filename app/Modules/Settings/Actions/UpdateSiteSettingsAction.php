<?php

namespace App\Modules\Settings\Actions;

use App\Modules\Settings\Models\SiteSetting;
use App\Modules\Settings\Support\SiteSettingsRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateSiteSettingsAction
{
    public function __construct(private readonly SiteSettingsRegistry $registry) {}

    /** @param array<string, mixed> $validated */
    public function execute(array $validated): void
    {
        DB::transaction(function () use ($validated): void {
            $this->saveAsset('logo_path', $validated['logo'] ?? null);
            $this->saveAsset('favicon_path', $validated['favicon'] ?? null);

            foreach (config('site-settings.defaults', []) as $key => $default) {
                if (in_array($key, ['logo_path', 'favicon_path'], true)) {
                    continue;
                }

                $isSecret = in_array($key, config('site-settings.secrets', []), true);
                $value = $validated[$key] ?? null;

                if ($isSecret && ($value === null || $value === '')) {
                    continue;
                }

                SiteSetting::query()->updateOrCreate(['key' => $key], [
                    'value' => $isSecret && $value !== null ? Crypt::encryptString((string) $value) : (string) ($value ?? ''),
                    'is_secret' => $isSecret,
                ]);
            }
        });

        $this->registry->clear();
    }

    private function saveAsset(string $key, mixed $file): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $existing = SiteSetting::query()->where('key', $key)->first();
        if ($existing?->value) {
            Storage::disk('public')->delete($existing->value);
        }

        SiteSetting::query()->updateOrCreate(['key' => $key], [
            'value' => $file->store('settings', 'public'),
            'is_secret' => false,
        ]);
    }
}
