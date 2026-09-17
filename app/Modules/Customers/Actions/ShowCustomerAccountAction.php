<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Billing\Actions\ResolveClarePayWalletAction;
use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Orders\Models\Order;

class ShowCustomerAccountAction
{
    public function __construct(private readonly ResolveClarePayWalletAction $resolveWallet) {}

    public function execute(User $user): array
    {
        $wallet = $this->resolveWallet->execute($user);

        return [
            'orders' => Order::query()
                ->where('user_id', $user->getKey())
                ->with([
                    'discount',
                    'items.variant.images',
                    'items.variant.product.images',
                ])
                ->orderByDesc('placed_at')
                ->limit(6)
                ->get(),
            'appointments' => Appointment::query()
                ->where('user_id', $user->getKey())
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
            'defaultAddress' => $user->addresses()
                ->where('is_default', true)
                ->first(),
            'addresses' => $user->addresses()
                ->orderByDesc('is_default')
                ->latest()
                ->get(),
            'orderCount' => Order::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'appointmentCount' => Appointment::query()
                ->where('user_id', $user->getKey())
                ->count(),
            'voucherCount' => $user->vouchers()->count(),
            'clarePayWallet' => $wallet,
            'clarePayTransactions' => $wallet->transactions()->latest()->limit(20)->get(),
            'payLaterPurchases' => PayLaterPurchase::query()
                ->where('user_id', $user->getKey())
                ->with('order')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'billingNotifications' => $user->notifications()->latest()->limit(20)->get(),
            'unreadBillingNotificationCount' => $user->unreadNotifications()->count(),
            'wishlistProducts' => $user->wishlistProducts()
                ->published()
                ->withStorefrontSummary()
                ->with(['category', 'images'])
                ->limit(8)
                ->get(),
            'recentlyViewedProducts' => $user->recentlyViewedProducts()
                ->published()
                ->withStorefrontSummary()
                ->with(['category', 'images'])
                ->limit(8)
                ->get(),
        ];
    }
}
