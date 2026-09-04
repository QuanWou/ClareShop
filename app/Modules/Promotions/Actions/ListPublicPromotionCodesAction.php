<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Support\Collection;

class ListPublicPromotionCodesAction
{
    /** @return Collection<int, array{promotion: PromotionCode, state: string, label: string, message: string, claimed: bool, claimable: bool, remaining_claims: ?int}> */
    public function execute(?User $customer = null): Collection
    {
        $promotions = PromotionCode::query()
            ->where('is_public', true)
            ->orderByDesc('is_active')
            ->orderBy('ends_at')
            ->latest('id')
            ->get();

        $claimedByPromotion = $customer === null
            ? collect()
            : $customer->vouchers()
                ->whereIn('promotion_code_id', $promotions->pluck('id'))
                ->get()
                ->keyBy('promotion_code_id');

        return $promotions->map(function (PromotionCode $promotion) use ($claimedByPromotion): array {
            $claimed = $claimedByPromotion->has($promotion->getKey());
            [$state, $label, $message] = $this->stateFor($promotion, $claimed);

            return [
                'promotion' => $promotion,
                'state' => $state,
                'label' => $label,
                'message' => $message,
                'claimed' => $claimed,
                'claimable' => ! $claimed && $promotion->isClaimableNow(),
                'remaining_claims' => $promotion->claim_limit === null
                    ? null
                    : max(0, $promotion->claim_limit - $promotion->claim_count),
            ];
        });
    }

    /** @return array{string, string, string} */
    private function stateFor(PromotionCode $promotion, bool $claimed): array
    {
        if ($claimed) {
            return ['saved', 'Đã lưu', 'Voucher đã có trong Ví voucher của bạn.'];
        }

        if ($promotion->starts_at?->isFuture()) {
            return ['upcoming', 'Chưa bắt đầu', 'Voucher sẽ mở nhận từ '.$promotion->starts_at->format('H:i, d/m/Y').'.'];
        }

        if ($promotion->ends_at?->isPast()) {
            return ['expired', 'Đã hết hạn', 'Thời gian nhận voucher đã kết thúc.'];
        }

        if (! $promotion->is_active) {
            return ['disabled', 'Đang tạm dừng', 'Voucher hiện chưa được áp dụng tại checkout.'];
        }

        if ($promotion->claim_limit !== null && $promotion->claim_count >= $promotion->claim_limit) {
            return ['claimed_out', 'Đã hết số lượng', 'Voucher đã được nhận hết số lượng phát hành.'];
        }

        return ['available', 'Có thể nhận', 'Nhận vào tài khoản để dùng tại checkout.'];
    }
}
