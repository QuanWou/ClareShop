<?php

namespace Database\Seeders;

use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        PromotionCode::query()->firstOrCreate(
            ['code' => 'CLARE10'],
            [
                'name' => 'Ưu đãi chào nhà mới',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'minimum_order_amount' => 500000,
                'maximum_discount_amount' => 300000,
                'usage_limit' => 100,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ],
        );
    }
}
