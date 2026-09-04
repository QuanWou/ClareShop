<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Promotions\Models\PromotionCode;

class ArchivePromotionCodeAction
{
    public function execute(PromotionCode $promotion): PromotionCode
    {
        $promotion->update(['is_active' => false]);

        return $promotion->refresh();
    }
}
