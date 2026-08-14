<?php

namespace App\Modules\Shared\Support;

class Money
{
    public static function formatVnd(int|float|string $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').' VND';
    }
}
