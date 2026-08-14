<?php

namespace App\Modules\Cart\Data;

use App\Modules\Cart\Models\Cart;

readonly class CartResolution
{
    public function __construct(
        public ?Cart $cart,
        public ?string $guestToken = null,
        public bool $attachGuestCookie = false,
        public bool $forgetGuestCookie = false,
    ) {}
}
