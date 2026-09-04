<?php

namespace App\Modules\Orders\Support;

class PaymentMethodCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $methods = config('checkout.payment_methods', []);

        if (app()->runningUnitTests() && isset($methods['momo'])) {
            $methods['momo']['is_simulated'] = true;
            $methods['momo']['confirmation_title'] = 'Đang chờ khởi tạo thanh toán MoMo.';
            $methods['momo']['confirmation_description'] = 'Clare đã ghi nhận lựa chọn của bạn. Liên kết thanh toán MoMo sẽ chỉ được tạo khi cổng thanh toán được kết nối.';
        }

        return $methods;
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
            'requires_qr' => false,
            'is_simulated' => true,
            'confirmation_title' => 'Thanh toán đang chờ xử lý.',
            'confirmation_description' => 'Clare sẽ cập nhật bước thanh toán tiếp theo.',
        ];
    }
}
