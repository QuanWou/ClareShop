<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Orders\Data\CheckoutTotalsData;
use App\Modules\Orders\Data\ShippingAddressData;
use Illuminate\Validation\ValidationException;

class QuoteCheckoutAction
{
    public function __construct(private readonly CalculateCheckoutTotalsAction $calculateCheckoutTotals) {}

    public function execute(
        ?Cart $cart,
        ShippingAddressData $address,
        ?string $discountCode = null,
        ?string $shippingOption = null,
    ): CheckoutTotalsData
    {
        if ($cart === null) {
            throw ValidationException::withMessages([
                'cart' => 'Giỏ hàng đang trống.',
            ]);
        }

        return $this->calculateCheckoutTotals->execute(
            cart: $cart,
            address: $address,
            discountCode: $discountCode,
            shippingOption: $shippingOption,
            ignoreInvalidDiscount: true,
            includeShippingOptions: true,
        );
    }
}
