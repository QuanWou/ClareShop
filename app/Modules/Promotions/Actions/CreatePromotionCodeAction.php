<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Promotions\Models\PromotionCode;

class CreatePromotionCodeAction
{
    public function execute(array $validated): PromotionCode
    {
        return PromotionCode::query()->create($validated);
    }
}
