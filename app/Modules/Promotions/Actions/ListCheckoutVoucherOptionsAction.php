<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ListCheckoutVoucherOptionsAction
{
    public function __construct(private readonly CalculatePromotionDiscountAction $calculateDiscount) {}

    /** @return Collection<int, array{voucher: UserVoucher, eligible: bool, amount: int, reason: ?string}> */
    public function execute(User $customer, int $subtotal): Collection
    {
        return $customer->vouchers()
            ->with('promotionCode')
            ->latest('claimed_at')
            ->get()
            ->map(function (UserVoucher $voucher) use ($customer, $subtotal): array {
                $promotion = $voucher->promotionCode;

                if ($promotion === null) {
                    return ['voucher' => $voucher, 'eligible' => false, 'amount' => 0, 'reason' => 'Voucher không còn khả dụng.'];
                }

                try {
                    $discount = $this->calculateDiscount->execute($promotion->code, $subtotal, false, $customer);

                    return ['voucher' => $voucher, 'eligible' => true, 'amount' => $discount->amount, 'reason' => null];
                } catch (ValidationException $exception) {
                    return [
                        'voucher' => $voucher,
                        'eligible' => false,
                        'amount' => 0,
                        'reason' => $exception->errors()['discount_code'][0] ?? 'Voucher chưa đủ điều kiện.',
                    ];
                }
            });
    }
}
