<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Data\ShippingQuoteData;
use Illuminate\Support\Str;

class EstimateShippingRateAction
{
    public function __construct(private readonly CalculateEstimatedDeliveryAtAction $calculateEstimatedDeliveryAt) {}

    public function execute(ShippingAddressData $address, int $totalWeightGrams): ShippingQuoteData
    {
        $estimate = config('checkout.shipping.estimate');
        $includedWeight = $estimate['included_weight_grams'];
        $additionalWeight = max(0, $totalWeightGrams - $includedWeight);
        $additionalBlocks = (int) ceil($additionalWeight / $estimate['additional_weight_block_grams']);
        $city = Str::lower(Str::ascii($address->city));
        $isUrban = in_array($city, $estimate['urban_cities'], true);
        $outsideUrbanSurcharge = $isUrban ? 0 : $estimate['outside_urban_area_surcharge'];
        $fee = $estimate['base_fee']
            + ($additionalBlocks * $estimate['additional_weight_fee'])
            + $outsideUrbanSurcharge;

        return new ShippingQuoteData(
            provider: 'clare_estimate',
            service: 'Giao tiêu chuẩn (ước tính)',
            quoteId: null,
            fee: $fee,
            totalWeightGrams: $totalWeightGrams,
            estimatedDays: $isUrban ? 2 : 4,
            estimatedDeliveryAt: $this->calculateEstimatedDeliveryAt->execute(now(), $isUrban ? 2 : 4),
            isEstimated: true,
            payload: [
                'driver' => config('checkout.shipping.driver'),
                'weight_grams' => $totalWeightGrams,
                'base_fee' => $estimate['base_fee'],
                'included_weight_grams' => $includedWeight,
                'additional_weight_blocks' => $additionalBlocks,
                'additional_weight_block_grams' => $estimate['additional_weight_block_grams'],
                'additional_weight_fee' => $estimate['additional_weight_fee'],
                'outside_urban_area_surcharge' => $outsideUrbanSurcharge,
                'is_urban_destination' => $isUrban,
            ],
        );
    }
}
