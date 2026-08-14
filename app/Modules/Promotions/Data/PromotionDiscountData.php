<?php

namespace App\Modules\Promotions\Data;

use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Shared\Support\Money;

readonly class PromotionDiscountData
{
    public function __construct(
        public ?PromotionCode $promotion,
        public ?string $code,
        public ?string $name,
        public ?string $type,
        public ?int $value,
        public int $amount,
        public ?string $message = null,
    ) {}

    public static function none(?string $message = null): self
    {
        return new self(null, null, null, null, null, 0, $message);
    }

    public function isApplied(): bool
    {
        return $this->promotion !== null && $this->amount > 0;
    }

    public function toArray(): array
    {
        return [
            'applied' => $this->isApplied(),
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'amount' => $this->amount,
            'amount_formatted' => Money::formatVnd($this->amount),
            'message' => $this->message,
        ];
    }

    public function toOrderDiscountAttributes(): array
    {
        return [
            'promotion_code_id' => $this->promotion?->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'discount_type' => $this->type,
            'discount_value' => $this->value,
            'discount_amount' => $this->amount,
        ];
    }
}
