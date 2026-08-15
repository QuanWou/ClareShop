<?php

namespace App\Modules\Orders\Support;

use LogicException;

class ShippingOptionCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('checkout.shipping.providers', []);
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function defaultCode(): string
    {
        $default = config('checkout.shipping.default_option');

        if (is_string($default) && array_key_exists($default, self::all())) {
            return $default;
        }

        $first = array_key_first(self::all());

        if (! is_string($first)) {
            throw new LogicException('Chưa có đơn vị vận chuyển được cấu hình.');
        }

        return $first;
    }

    /** @return array<string, mixed> */
    public static function get(?string $code): array
    {
        $resolvedCode = filled($code) ? $code : self::defaultCode();
        $option = self::all()[$resolvedCode] ?? null;

        if (! is_array($option)) {
            throw new LogicException('Đơn vị vận chuyển không hợp lệ.');
        }

        return ['code' => $resolvedCode, ...$option];
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCheckout(): array
    {
        return array_map(
            fn (string $code, array $option): array => ['code' => $code, ...$option],
            array_keys(self::all()),
            self::all(),
        );
    }
}
