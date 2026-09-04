<?php

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\MediaAsset;

class UpdateMediaAssetAction
{
    /** @param array{alt_text?: string|null} $validated */
    public function execute(MediaAsset $asset, array $validated): MediaAsset
    {
        $asset->update(['alt_text' => $validated['alt_text'] ?? null]);

        return $asset->refresh();
    }
}
