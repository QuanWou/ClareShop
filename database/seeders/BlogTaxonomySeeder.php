<?php

namespace Database\Seeders;

use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['cam-nang-chon-den', 'Cẩm nang chọn đèn'],
            ['chuyen-khong-gian', 'Chuyện không gian'],
            ['cham-soc-lap-dat', 'Chăm sóc & lắp đặt'],
        ] as $index => [$slug, $name]) {
            BlogCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        foreach (['Phòng ngủ', 'Ánh sáng ấm', 'Chọn kích thước', 'Lắp đặt', 'Bảo quản'] as $name) {
            BlogTag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
