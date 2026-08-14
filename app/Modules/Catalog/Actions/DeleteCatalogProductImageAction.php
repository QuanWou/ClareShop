<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class DeleteCatalogProductImageAction
{
    public function execute(ProductImage $image): void
    {
        $disk = $image->disk;
        $path = $image->path;
        $image->delete();

        if ($disk === 'public' && ! ProductImage::query()->where('disk', $disk)->where('path', $path)->exists()) {
            Storage::disk($disk)->delete($path);
        }
    }
}
