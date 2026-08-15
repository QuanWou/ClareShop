<?php

namespace App\Modules\Orders\Data;

use App\Modules\Shared\Support\Money;
use Carbon\CarbonImmutable;

readonly class ShippingQuoteData
{
    public function __construct(
        public string $provider,
        public string $service,
        public ?string $quoteId,
        public int $fee,
        public int $totalWeightGrams,
        public ?int $estimatedDays,
        public ?CarbonImmutable $estimatedDeliveryAt,
        public bool $isEstimated,
        public array $payload,
    ) {}

    public function toArray(): array
    {
        return [
            'option' => $this->payload['shipping_option'] ?? null,
            'provider' => $this->provider,
            'service' => $this->service,
            'quote_id' => $this->quoteId,
            'fee' => $this->fee,
            'fee_formatted' => Money::formatVnd($this->fee),
            'total_weight_grams' => $this->totalWeightGrams,
            'estimated_days' => $this->estimatedDays,
            'estimated_days_label' => $this->estimatedDays === null ? null : $this->estimatedDays.' ngày làm việc',
            'estimated_delivery_at' => $this->estimatedDeliveryAt?->toIso8601String(),
            'estimated_delivery_date_formatted' => $this->estimatedDeliveryAt?->locale('vi')->isoFormat('dddd, DD/MM'),
            'is_estimated' => $this->isEstimated,
            'calculation' => [
                'base_fee' => $this->payload['base_fee'] ?? null,
                'base_fee_formatted' => isset($this->payload['base_fee']) ? Money::formatVnd($this->payload['base_fee']) : null,
                'included_weight_grams' => $this->payload['included_weight_grams'] ?? null,
                'additional_weight_blocks' => $this->payload['additional_weight_blocks'] ?? 0,
                'additional_weight_block_grams' => $this->payload['additional_weight_block_grams'] ?? null,
                'additional_weight_fee' => $this->payload['additional_weight_fee'] ?? null,
                'additional_weight_fee_formatted' => isset($this->payload['additional_weight_fee']) ? Money::formatVnd($this->payload['additional_weight_fee']) : null,
                'destination_surcharge' => $this->payload['outside_urban_area_surcharge'] ?? 0,
                'destination_surcharge_formatted' => isset($this->payload['outside_urban_area_surcharge']) ? Money::formatVnd($this->payload['outside_urban_area_surcharge']) : null,
                'is_urban_destination' => $this->payload['is_urban_destination'] ?? null,
            ],
        ];
    }
}
