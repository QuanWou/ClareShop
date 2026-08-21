<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Media\Models\MediaAsset;

class ListAdminMediaAssetsAction
{
    public function execute(): array
    {
        return ['assets' => MediaAsset::query()->with('uploader')->latest()->paginate(30)];
    }
}
