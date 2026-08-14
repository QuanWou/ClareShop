<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tableLamps = Category::query()->updateOrCreate(
                ['slug' => 'den-ban'],
                [
                    'name' => 'Đèn bàn',
                    'description' => 'Những quầng sáng nhỏ cho đầu giường, bàn đọc sách và các khoảng nghỉ trong nhà.',
                    'image_path' => 'images/catalog/den-ban-thao-moc.png',
                    'is_active' => true,
                    'sort_order' => 10,
                ],
            );

            $wallLights = Category::query()->updateOrCreate(
                ['slug' => 'den-tuong'],
                [
                    'name' => 'Đèn tường',
                    'description' => 'Ánh sáng gọn gàng, dịu mắt cho lối đi, góc đọc và hai bên đầu giường.',
                    'image_path' => 'images/catalog/den-tuong-hoang-hon.png',
                    'is_active' => true,
                    'sort_order' => 20,
                ],
            );

            $this->seedProduct(
                category: $tableLamps,
                product: [
                    'slug' => 'ru-dem',
                    'name' => 'Ru Đêm',
                    'short_description' => 'Một vầng sáng êm với thân gốm tròn, vừa đủ để căn phòng chậm lại.',
                    'description' => 'Ru Đêm tạo nên quầng sáng mềm qua chụp thủy tinh opal. Dáng đèn thấp, cân đối giúp mẫu đèn hiện diện vừa phải trên tủ đầu giường mà vẫn trở thành một điểm nhấn ấm áp.',
                    'material' => 'Thủy tinh opal, gốm hoàn thiện bóng, chi tiết kim loại sơn tĩnh điện',
                    'dimensions' => 'Rộng 28 cm × cao 34 cm',
                    'is_active' => true,
                    'is_featured' => true,
                    'published_at' => now()->subDays(6),
                ],
                variants: [
                    [
                        'sku' => 'CLR-RD-BURGUNDY',
                        'color_name' => 'Đỏ vang',
                        'color_hex' => '#5A1F25',
                        'price' => 2490000,
                        'compare_at_price' => 2790000,
                        'stock_quantity' => 8,
                        'weight_grams' => 2200,
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'sku' => 'CLR-RD-CREAM',
                        'color_name' => 'Kem ấm',
                        'color_hex' => '#D8C5A5',
                        'price' => 2490000,
                        'compare_at_price' => null,
                        'stock_quantity' => 4,
                        'weight_grams' => 2200,
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
                images: [
                    [
                        'path' => 'images/catalog/hero-ru-dem.png',
                        'alt_text' => 'Đèn bàn Ru Đêm thân gốm đỏ vang và chụp opal trong phòng ngủ màu kem',
                    ],
                    [
                        'path' => 'images/catalog/den-ban-ru-dem-ngu.png',
                        'alt_text' => 'Đèn bàn Ru Đêm trên tủ đầu giường gỗ trong ánh sáng buổi chiều',
                    ],
                ],
            );

            $this->seedProduct(
                category: $tableLamps,
                product: [
                    'slug' => 'thao-moc',
                    'name' => 'Thảo Mộc',
                    'short_description' => 'Thân gốm thanh mảnh và chụp linen tự nhiên cho góc đọc yên tĩnh.',
                    'description' => 'Thảo Mộc kết hợp bề mặt gốm mờ với lớp vải linen dệt thô. Ánh sáng được tán đều, phù hợp để đọc nhẹ trước giờ ngủ hoặc làm dịu một góc phòng khách.',
                    'material' => 'Gốm thủ công, linen tự nhiên, đế kim loại',
                    'dimensions' => 'Rộng 30 cm × cao 57 cm',
                    'is_active' => true,
                    'is_featured' => true,
                    'published_at' => now()->subDays(4),
                ],
                variants: [
                    [
                        'sku' => 'CLR-TM-OLIVE',
                        'color_name' => 'Olive trầm',
                        'color_hex' => '#69643F',
                        'price' => 1890000,
                        'compare_at_price' => null,
                        'stock_quantity' => 12,
                        'weight_grams' => 2800,
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'sku' => 'CLR-TM-CHARCOAL',
                        'color_name' => 'Than nâu',
                        'color_hex' => '#403A33',
                        'price' => 1990000,
                        'compare_at_price' => null,
                        'stock_quantity' => 0,
                        'weight_grams' => 2800,
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
                images: [
                    [
                        'path' => 'images/catalog/den-ban-thao-moc.png',
                        'alt_text' => 'Đèn bàn Thảo Mộc thân gốm olive và chụp linen trên kệ gỗ sáng',
                    ],
                    [
                        'path' => 'images/catalog/den-ban-thao-moc-ngu.png',
                        'alt_text' => 'Đèn bàn Thảo Mộc trên tủ đầu giường cạnh rèm linen',
                    ],
                ],
            );

            $this->seedProduct(
                category: $wallLights,
                product: [
                    'slug' => 'hoang-hon',
                    'name' => 'Hoàng Hôn',
                    'short_description' => 'Đèn tường opal gọn nhẹ, cho một lớp sáng dịu như cuối ngày.',
                    'description' => 'Hoàng Hôn dành cho những vị trí cần ánh sáng êm mà không chiếm mặt bàn. Quả cầu opal tán sáng đều, tay đèn màu đồng sẫm tạo một đường nét điềm tĩnh trên tường.',
                    'material' => 'Thủy tinh opal, nhôm hoàn thiện đồng xước',
                    'dimensions' => 'Rộng 22 cm × sâu 25 cm × cao 26 cm',
                    'is_active' => true,
                    'is_featured' => true,
                    'published_at' => now()->subDays(2),
                ],
                variants: [
                    [
                        'sku' => 'CLR-HH-BRONZE',
                        'color_name' => 'Đồng sẫm',
                        'color_hex' => '#4A3327',
                        'price' => 1690000,
                        'compare_at_price' => 1890000,
                        'stock_quantity' => 6,
                        'weight_grams' => 1400,
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'sku' => 'CLR-HH-BRASS',
                        'color_name' => 'Đồng ấm',
                        'color_hex' => '#9B7448',
                        'price' => 1790000,
                        'compare_at_price' => null,
                        'stock_quantity' => 3,
                        'weight_grams' => 1400,
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
                images: [
                    [
                        'path' => 'images/catalog/den-tuong-hoang-hon.png',
                        'alt_text' => 'Đèn tường Hoàng Hôn với cầu opal trên nền tường màu đất nung',
                    ],
                    [
                        'path' => 'images/catalog/den-tuong-hoang-hon-doc-sach.png',
                        'alt_text' => 'Đèn tường Hoàng Hôn trong góc đọc sách tông đất nung',
                    ],
                ],
            );
        });

        $this->call(SupplementalCatalogSeeder::class);
        $this->call(NewArrivalCatalogSeeder::class);
    }

    private function seedProduct(
        Category $category,
        array $product,
        array $variants,
        array $images,
    ): void {
        $productModel = Product::query()->updateOrCreate(
            ['slug' => $product['slug']],
            ['category_id' => $category->getKey(), ...$product],
        );

        foreach ($variants as $variant) {
            $productModel->variants()->updateOrCreate(
                ['sku' => $variant['sku']],
                $variant,
            );
        }

        foreach ($images as $sortOrder => $image) {
            $productModel->images()->updateOrCreate(
                ['path' => $image['path']],
                [
                    'product_variant_id' => null,
                    'disk' => 'asset',
                    'alt_text' => $image['alt_text'],
                    'sort_order' => ($sortOrder + 1) * 10,
                ],
            );
        }
    }
}
