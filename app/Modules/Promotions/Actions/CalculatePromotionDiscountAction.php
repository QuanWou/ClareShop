<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Data\PromotionDiscountData;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use App\Modules\Promotions\Models\VoucherReservation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CalculatePromotionDiscountAction
{
    public function execute(
        ?string $submittedCode,
        int $subtotal,
        bool $lockForUpdate = false,
        ?User $customer = null,
    ): PromotionDiscountData
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

        $reservedCount = VoucherReservation::query()
            ->where('promotion_code_id', $promotion->getKey())
            ->where('status', VoucherReservation::STATUS_RESERVED)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($lockForUpdate) {
            $reservedCount->lockForUpdate();
        }

        if ($promotion->usage_limit !== null && ($promotion->usage_count + $reservedCount->count()) >= $promotion->usage_limit) {
            $this->invalid('Mã ưu đãi đã hết lượt sử dụng.');
        }

        $userVoucher = $this->resolveUserVoucher($promotion, $customer, $lockForUpdate);

        $minimumOrderAmount = $promotion->minimum_order_amount === null ? null : (int) $promotion->minimum_order_amount;

        if ($minimumOrderAmount !== null && $subtotal < $minimumOrderAmount) {
            $this->invalid('Mã ưu đãi áp dụng cho đơn từ '.number_format($minimumOrderAmount, 0, ',', '.').' VND.');
        }

        $maximumOrderAmount = $promotion->maximum_order_amount === null ? null : (int) $promotion->maximum_order_amount;

        if ($maximumOrderAmount !== null && $subtotal > $maximumOrderAmount) {
            $this->invalid('Mã ưu đãi chỉ áp dụng cho đơn tối đa '.number_format($maximumOrderAmount, 0, ',', '.').' VND.');
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
            userVoucher: $userVoucher,
            code: $promotion->code,
            name: $promotion->name,
            type: $promotion->discount_type,
            value: $value,
            amount: $amount,
        );
    }

    private function resolveUserVoucher(PromotionCode $promotion, ?User $customer, bool $lockForUpdate): ?UserVoucher
    {
        if (! $promotion->requires_claim) {
            return null;
        }

        if ($customer === null) {
            $this->invalid('Hãy nhận mã ưu đãi vào tài khoản trước khi sử dụng.');
        }

        $query = UserVoucher::query()
            ->where('user_id', $customer->getKey())
            ->where('promotion_code_id', $promotion->getKey());

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if ($voucher === null) {
            $this->invalid('Mã ưu đãi này chưa có trong Ví voucher của bạn.');
        }

        if ($voucher->used_count >= $promotion->per_user_usage_limit) {
            $this->invalid('Bạn đã dùng hết số lượt cá nhân của mã ưu đãi này.');
        }

        $activeReservation = VoucherReservation::query()
            ->where('user_voucher_id', $voucher->getKey())
            ->where('status', VoucherReservation::STATUS_RESERVED)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($lockForUpdate) {
            $activeReservation->lockForUpdate();
        }

        if ($activeReservation->exists()) {
            $this->invalid('Voucher này đang được giữ cho một đơn chờ thanh toán khác.');
        }

        return $voucher;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['discount_code' => $message]);
    }
}
