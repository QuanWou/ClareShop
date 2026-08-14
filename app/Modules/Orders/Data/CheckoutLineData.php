<?php

namespace App\Modules\Orders\Data;

use App\Modules\Shared\Support\Money;

readonly class CheckoutLineData
{
    public function __construct(
        public int $variantId,
        public string $productName,
        public ?string $productSlug,
        public string $colorName,
        public string $sku,
        public ?string $imagePath,
        public int $unitPrice,
        public int $quantity,
        public int $lineTotal,
        public int $weightGrams,
        public int $stockQuantity,
    ) {}

    public function toArray(): array
    {
        return [
            'product_variant_id' => $this->variantId,
            'product_name' => $this->productName,
            'product_slug' => $this->productSlug,
            'color_name' => $this->colorName,
            'sku' => $this->sku,
            'image_path' => $this->imagePath,
            'unit_price' => $this->unitPrice,
            'unit_price_formatted' => Money::formatVnd($this->unitPrice),
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
            'line_total_formatted' => Money::formatVnd($this->lineTotal),
            'weight_grams' => $this->weightGrams,
        ];
    }

    public function toOrderItemAttributes(): array
    {
        return [
            'product_variant_id' => $this->variantId,
            'product_name' => $this->productName,
            'product_slug' => $this->productSlug,
            'color_name' => $this->colorName,
            'sku' => $this->sku,
            'image_path' => $this->imagePath,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
        ];
    }
}
