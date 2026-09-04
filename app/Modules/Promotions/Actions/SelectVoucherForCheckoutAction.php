<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SelectVoucherForCheckoutAction
{
    public function execute(User $customer, UserVoucher $voucher): string
    {
        if ((int) $voucher->user_id !== (int) $customer->getKey()) {
            throw (new ModelNotFoundException)->setModel(UserVoucher::class, [$voucher->getKey()]);
        }

        $voucher->loadMissing('promotionCode');

        return (string) $voucher->promotionCode?->code;
    }
}
