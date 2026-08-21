<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductReview;

class ModerateProductReviewAction
{
    /** @param array{status: string, moderation_note?: string|null} $validated */
    public function execute(ProductReview $review, array $validated): ProductReview
    {
        $review->update([
            'status' => $validated['status'],
            'moderation_note' => $validated['moderation_note'] ?? null,
            'approved_at' => $validated['status'] === 'approved' ? ($review->approved_at ?? now()) : null,
        ]);

        return $review->refresh();
    }
}
