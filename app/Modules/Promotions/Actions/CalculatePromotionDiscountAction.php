<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Promotions\Data\PromotionDiscountData;
use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CalculatePromotionDiscountAction
{
    public function execute(?string $submittedCode, int $subtotal, bool $lockForUpdate = false): PromotionDiscountData
    {
        $code = Str::upper(trim((string) $submittedCode));

        if ($code === '') {
            return PromotionDiscountData::none();
        }

        $promotionQuery = PromotionCode::query()->where('code', $code);

        if ($lockForUpdate) {
            $promotionQuery->lockForUpdate();
        }

        $promotion = $promotionQuery->first();

        if ($promotion === null) {
            $this->invalid('Mã ưu đãi không hợp lệ.');
        }

        if (! $promotion->is_active || ($promotion->starts_at?->isFuture() ?? false) || ($promotion->ends_at?->isPast() ?? false)) {
            $this->invalid('Mã ưu đãi hiện không còn hiệu lực.');
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            $this->invalid('Mã ưu đãi đã hết lượt sử dụng.');
        }

        $minimumOrderAmount = $promotion->minimum_order_amount === null ? null : (int) $promotion->minimum_order_amount;

        if ($minimumOrderAmount !== null && $subtotal < $minimumOrderAmount) {
            $this->invalid('Mã ưu đãi áp dụng cho đơn từ '.number_format($minimumOrderAmount, 0, ',', '.').' VND.');
        }

        $value = (int) round((float) $promotion->discount_value);
        $amount = $promotion->discount_type === 'percentage'
            ? (int) floor($subtotal * ($value / 100))
            : min($subtotal, $value);

        if ($promotion->maximum_discount_amount !== null) {
            $amount = min($amount, (int) $promotion->maximum_discount_amount);
        }

        if ($amount <= 0) {
            $this->invalid('Mã ưu đãi chưa tạo được mức giảm hợp lệ cho đơn này.');
        }

        return new PromotionDiscountData(
            promotion: $promotion,
            code: $promotion->code,
            name: $promotion->name,
            type: $promotion->discount_type,
            value: $value,
            amount: $amount,
        );
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['discount_code' => $message]);
    }
}
