<?php

namespace App\Modules\Orders\Support;

class PaymentMethodCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('checkout.payment_methods', []);
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, mixed> */
    public static function get(string $code): array
    {
        return self::all()[$code] ?? [
            'label' => $code,
            'short_label' => $code,
            'description' => 'Phương thức thanh toán đã được ghi nhận.',
            'provider' => $code,
            'initial_status' => 'pending',
            'requires_vietqr' => false,
            'is_simulated' => true,
            'confirmation_title' => 'Thanh toán đang chờ xử lý.',
            'confirmation_description' => 'Clare sẽ cập nhật bước thanh toán tiếp theo.',
        ];
    }
}
