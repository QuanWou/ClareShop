<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Validation\ValidationException;

class UpdatePromotionCodeAction
{
    public function execute(PromotionCode $promotion, array $validated): PromotionCode
    {
        if (($validated['usage_limit'] ?? null) !== null && $validated['usage_limit'] < $promotion->usage_count) {
            throw ValidationException::withMessages([
                'usage_limit' => 'Giới hạn lượt dùng không thể thấp hơn '.$promotion->usage_count.' lượt đã sử dụng.',
            ]);
        }

        $promotion->update($validated);

        return $promotion->fresh();
    }
}
