<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminPromotionCodesAction
{
    public function execute(): LengthAwarePaginator
    {
        return PromotionCode::query()
            ->latest('id')
            ->paginate(20);
    }
}
