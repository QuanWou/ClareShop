<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteProductReviewAction
{
    public function execute(ProductReview $review): void
    {
        $paths = $review->images()->pluck('path')->all();

        DB::transaction(fn () => $review->delete());

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
