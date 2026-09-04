<?php

namespace Database\Seeders;

use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $common = [
            'is_active' => true,
            'is_public' => true,
            'requires_claim' => true,
            'application_scope' => 'order',
            'usage_limit' => 100,
            'claim_limit' => 100,
            'per_user_usage_limit' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(60),
        ];

        foreach ([
            ['code' => 'TESTCLARE10', 'name' => 'Chào nhà mới 10%', 'description' => 'Giảm 10% cho đơn đầu tiên đủ điều kiện.', 'discount_type' => 'percentage', 'discount_value' => 10, 'minimum_order_amount' => 300000, 'maximum_discount_amount' => 100000],
            ['code' => 'TESTCLARE15', 'name' => 'Không gian dịu 15%', 'description' => 'Ưu đãi 15% với mức giảm tối đa 150.000 VND.', 'discount_type' => 'percentage', 'discount_value' => 15, 'minimum_order_amount' => 500000, 'maximum_discount_amount' => 150000],
            ['code' => 'TESTCLARE20', 'name' => 'Ánh sáng 20%', 'description' => 'Ưu đãi 20% cho đơn từ 1.000.000 VND.', 'discount_type' => 'percentage', 'discount_value' => 20, 'minimum_order_amount' => 1000000, 'maximum_discount_amount' => 200000],
            ['code' => 'TESTCLARE5', 'name' => 'Dịu dàng 5%', 'description' => 'Mã giảm nhẹ cho mọi đơn từ 100.000 VND.', 'discount_type' => 'percentage', 'discount_value' => 5, 'minimum_order_amount' => 100000, 'maximum_discount_amount' => 50000],
            ['code' => 'TESTCLARE25', 'name' => 'Bộ sưu tập 25%', 'description' => 'Giảm 25%, tối đa 300.000 VND.', 'discount_type' => 'percentage', 'discount_value' => 25, 'minimum_order_amount' => 1200000, 'maximum_discount_amount' => 300000],
            ['code' => 'TESTCLARE50K', 'name' => 'Quà 50.000 VND', 'description' => 'Giảm thẳng 50.000 VND cho đơn từ 500.000 VND.', 'discount_type' => 'fixed', 'discount_value' => 50000, 'minimum_order_amount' => 500000, 'maximum_discount_amount' => null],
            ['code' => 'TESTCLARE100K', 'name' => 'Quà 100.000 VND', 'description' => 'Giảm thẳng 100.000 VND cho đơn từ 1.000.000 VND.', 'discount_type' => 'fixed', 'discount_value' => 100000, 'minimum_order_amount' => 1000000, 'maximum_discount_amount' => null],
            ['code' => 'TESTCLARE150K', 'name' => 'Quà 150.000 VND', 'description' => 'Giảm thẳng 150.000 VND cho đơn từ 1.500.000 VND.', 'discount_type' => 'fixed', 'discount_value' => 150000, 'minimum_order_amount' => 1500000, 'maximum_discount_amount' => null],
            ['code' => 'TESTCLARE200K', 'name' => 'Quà 200.000 VND', 'description' => 'Giảm thẳng 200.000 VND cho đơn từ 2.000.000 VND.', 'discount_type' => 'fixed', 'discount_value' => 200000, 'minimum_order_amount' => 2000000, 'maximum_discount_amount' => null],
            ['code' => 'TESTCLAREVIP', 'name' => 'Clare khách thân thiết', 'description' => 'Giảm 250.000 VND cho đơn từ 2.500.000 VND.', 'discount_type' => 'fixed', 'discount_value' => 250000, 'minimum_order_amount' => 2500000, 'maximum_discount_amount' => null],
        ] as $voucher) {
            PromotionCode::query()->updateOrCreate(
                ['code' => $voucher['code']],
                [...$common, ...$voucher],
            );
        }
    }
}
