<?php

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

class DeleteMediaAssetAction
{
    public function execute(MediaAsset $asset): void
    {
        Storage::disk('public')->delete($asset->path);
        $asset->delete();
    }
}
