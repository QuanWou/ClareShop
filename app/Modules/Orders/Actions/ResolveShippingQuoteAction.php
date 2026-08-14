<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Data\ShippingQuoteData;
use LogicException;

class ResolveShippingQuoteAction
{
    public function __construct(private readonly EstimateShippingRateAction $estimateShippingRate) {}

    public function execute(ShippingAddressData $address, int $totalWeightGrams): ShippingQuoteData
    {
        return match (config('checkout.shipping.driver')) {
            'estimate' => $this->estimateShippingRate->execute($address, $totalWeightGrams),
            default => throw new LogicException('Shipping rate driver chưa được cấu hình.'),
        };
    }
}
