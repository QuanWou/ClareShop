<?php

namespace App\Modules\Billing\Actions;

use App\Models\User;
use App\Modules\Billing\Models\ClarePayWallet;

class ResolveClarePayWalletAction
{
    public function execute(User $user): ClarePayWallet
    {
        return ClarePayWallet::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['balance' => 0],
        );
    }
}
