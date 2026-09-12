<?php

namespace App\Modules\Chat\Actions;

use App\Modules\Chat\Models\ChatSetting;
use Illuminate\Support\Facades\DB;

class UpdateChatSettingsAction
{
    /** @param array<string, mixed> $data */
    public function execute(array $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                ChatSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
                );
            }
        });
    }
}
