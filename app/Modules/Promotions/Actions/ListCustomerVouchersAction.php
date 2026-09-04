<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Models\UserVoucher;
use App\Modules\Promotions\Models\VoucherReservation;
use Illuminate\Support\Collection;

class ListCustomerVouchersAction
{
    /** @return Collection<int, array{voucher: UserVoucher, state: string, label: string, message: string}> */
    public function execute(User $customer, ?string $filter = null): Collection
    {
        $rows = $customer->vouchers()
            ->with([
                'promotionCode',
                'reservations' => fn ($query) => $query
                    ->where('status', VoucherReservation::STATUS_RESERVED)
                    ->where(fn ($reservation) => $reservation->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            ])
            ->latest('claimed_at')
            ->get()
            ->map(function (UserVoucher $voucher): array {
                [$state, $label, $message] = $this->stateFor($voucher);

                return compact('voucher', 'state', 'label', 'message');
            });

        return $filter === null || $filter === 'all'
            ? $rows
            : $rows->where('state', $filter)->values();
    }

    /** @return array{string, string, string} */
    private function stateFor(UserVoucher $voucher): array
    {
        $promotion = $voucher->promotionCode;

        if ($promotion === null) {
            return ['unavailable', 'Không còn khả dụng', 'Dữ liệu voucher gốc không còn khả dụng.'];
        }

        if ($voucher->used_count >= $promotion->per_user_usage_limit) {
            return ['used', 'Đã sử dụng hết lượt', 'Bạn đã dùng hết số lượt cá nhân của voucher này.'];
        }

        if ($voucher->reservations->isNotEmpty()) {
            return ['reserved', 'Đang được giữ', 'Voucher đang được giữ cho một đơn chờ thanh toán.'];
        }

        if ($promotion->starts_at?->isFuture()) {
            return ['upcoming', 'Chưa đến thời gian dùng', 'Voucher mở dùng từ '.$promotion->starts_at->format('H:i, d/m/Y').'.'];
        }

        if ($promotion->ends_at?->isPast()) {
            return ['expired', 'Đã hết hạn', 'Thời gian sử dụng voucher đã kết thúc.'];
        }

        if (! $promotion->is_active) {
            return ['unavailable', 'Tạm không áp dụng', 'Voucher đang được tạm dừng tại checkout.'];
        }

        return ['available', 'Có thể sử dụng', 'Hệ thống sẽ kiểm tra điều kiện đơn hàng khi bạn chọn dùng.'];
    }
}
