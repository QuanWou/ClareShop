<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Data\PromotionDiscountData;
use Illuminate\Validation\ValidationException;

class GetCartVoucherPreviewAction
{
    public function __construct(private readonly CalculatePromotionDiscountAction $calculateDiscount) {}

    public function execute(?User $customer, ?string $code, int $subtotal): PromotionDiscountData
    {
        if (blank($code)) {
            return PromotionDiscountData::none();
        }

        try {
            return $this->calculateDiscount->execute($code, $subtotal, false, $customer);
        } catch (ValidationException $exception) {
            return PromotionDiscountData::none($exception->errors()['discount_code'][0] ?? 'Voucher chưa thể áp dụng.');
        }
    }
}
