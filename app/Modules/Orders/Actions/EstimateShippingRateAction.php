<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Data\ShippingQuoteData;
use App\Modules\Orders\Support\ShippingOptionCatalog;
use Illuminate\Support\Str;

class EstimateShippingRateAction
{
    public function __construct(private readonly CalculateEstimatedDeliveryAtAction $calculateEstimatedDeliveryAt) {}

    public function execute(ShippingAddressData $address, int $totalWeightGrams, ?string $shippingOption = null): ShippingQuoteData
    {
        $estimate = config('checkout.shipping.estimate');
        $option = ShippingOptionCatalog::get($shippingOption);
        $includedWeight = $option['included_weight_grams'];
        $additionalWeight = max(0, $totalWeightGrams - $includedWeight);
        $additionalBlocks = (int) ceil($additionalWeight / $option['additional_weight_block_grams']);
        $city = Str::lower(Str::ascii($address->city));
        $isUrban = in_array($city, $estimate['urban_cities'], true);
        $outsideUrbanSurcharge = $isUrban ? 0 : $option['outside_urban_area_surcharge'];
        $estimatedDays = $isUrban ? $option['urban_estimated_days'] : $option['outside_urban_estimated_days'];
        $fee = $option['base_fee']
            + ($additionalBlocks * $option['additional_weight_fee'])
            + $outsideUrbanSurcharge;

        return new ShippingQuoteData(
            provider: $option['label'],
            service: $option['service'],
            quoteId: 'EST-'.strtoupper($option['code']).'-'.now()->format('ymdHis'),
            fee: $fee,
            totalWeightGrams: $totalWeightGrams,
            estimatedDays: $estimatedDays,
            estimatedDeliveryAt: $this->calculateEstimatedDeliveryAt->execute(now(), $estimatedDays),
            isEstimated: true,
            payload: [
                'driver' => config('checkout.shipping.driver'),
                'shipping_option' => $option['code'],
                'provider_label' => $option['label'],
                'service_label' => $option['service'],
                'weight_grams' => $totalWeightGrams,
                'base_fee' => $option['base_fee'],
                'included_weight_grams' => $includedWeight,
                'additional_weight_blocks' => $additionalBlocks,
                'additional_weight_block_grams' => $option['additional_weight_block_grams'],
                'additional_weight_fee' => $option['additional_weight_fee'],
                'outside_urban_area_surcharge' => $outsideUrbanSurcharge,
                'is_urban_destination' => $isUrban,
            ],
        );
    }
}
