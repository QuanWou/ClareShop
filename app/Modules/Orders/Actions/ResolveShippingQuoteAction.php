<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Data\ShippingQuoteData;
use LogicException;

class ResolveShippingQuoteAction
{
    public function __construct(private readonly EstimateShippingRateAction $estimateShippingRate) {}

    public function execute(ShippingAddressData $address, int $totalWeightGrams, ?string $shippingOption = null): ShippingQuoteData
    {
        return match (config('checkout.shipping.driver')) {
            'estimate' => $this->estimateShippingRate->execute($address, $totalWeightGrams, $shippingOption),
            default => throw new LogicException('Shipping rate driver chưa được cấu hình.'),
        };
    }

    /** @return array<int, ShippingQuoteData> */
    public function all(ShippingAddressData $address, int $totalWeightGrams): array
    {
        return array_map(
            fn (string $shippingOption): ShippingQuoteData => $this->execute($address, $totalWeightGrams, $shippingOption),
            \App\Modules\Orders\Support\ShippingOptionCatalog::codes(),
        );
    }
}
