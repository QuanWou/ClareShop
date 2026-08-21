<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewArrivalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = Category::query()
                ->whereIn('slug', ['den-ban', 'den-trang-tri', 'den-ngu-de-thuong'])
                ->get()
                ->keyBy('slug');

            foreach ($this->products() as $product) {
                $category = $categories->get($product['category']);

                if ($category === null) {
                    throw new \RuntimeException("Không tìm thấy danh mục {$product['category']} cho catalog mới.");
                }

                $productModel = Product::withTrashed()->updateOrCreate(
                    ['slug' => $product['slug']],
                    [
                        'category_id' => $category->getKey(),
                        'name' => $product['name'],
                        'short_description' => $product['short_description'],
                        'description' => $product['description'],
                        'material' => $product['material'],
                        'dimensions' => $product['dimensions'],
                        'is_active' => true,
                        'is_featured' => false,
                        'published_at' => now()->subMinutes($product['published_minutes_ago']),
                    ],
                );
                $productModel->categories()->syncWithoutDetaching([$category->getKey()]);

                $productModel->variants()->withTrashed()->updateOrCreate(
                    ['sku' => $product['variant']['sku']],
                    [
                        ...$product['variant'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                );

                $productModel->images()->updateOrCreate(
                    ['path' => $product['image']['path']],
                    [
                        'product_variant_id' => null,
                        'disk' => 'asset',
                        'alt_text' => $product['image']['alt_text'],
                        'sort_order' => 10,
                    ],
                );
            }
        });
    }

    private function products(): array
    {
        return [
            [
                'category' => 'den-ban',
                'slug' => 'bac-sang',
                'name' => 'Bậc Sáng',
                'short_description' => 'Dáng đèn cổ điển với chân đá xếp tầng và chụp vải xếp ly.',
                'description' => 'Bậc Sáng mang lớp ánh vàng ấm qua chụp vải xếp ly, kết hợp thân đồng thanh mảnh và chân đá tạo cảm giác vững chãi cho bàn đầu giường hoặc góc đọc.',
                'material' => 'Đá tự nhiên, kim loại hoàn thiện đồng và chụp vải xếp ly',
                'dimensions' => 'Rộng 38 cm × cao 64 cm',
                'published_minutes_ago' => 5,
                'variant' => [
                    'sku' => 'CLR-BS-BRASS',
                    'color_name' => 'Đồng cổ & kem',
                    'color_hex' => '#A77A42',
                    'price' => 2390000,
                    'compare_at_price' => 2590000,
                    'stock_quantity' => 5,
                    'weight_grams' => 4200,
                ],
                'image' => [
                    'path' => 'images/catalog/moi1.png',
                    'alt_text' => 'Đèn bàn Bậc Sáng với thân đồng, chân đá xếp tầng và chụp vải xếp ly',
                ],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'binh-reu',
                'name' => 'Bình Rêu',
                'short_description' => 'Thân gốm xanh rêu tròn đầy dưới lớp chụp linen màu kem.',
                'description' => 'Bình Rêu giữ bảng màu trầm và dịu, với thân gốm xanh sâu cân bằng cùng chụp linen tán sáng đều cho bàn cạnh giường và kệ thấp.',
                'material' => 'Gốm hoàn thiện mờ, linen và chi tiết kim loại màu đồng',
                'dimensions' => 'Rộng 32 cm × cao 49 cm',
                'published_minutes_ago' => 10,
                'variant' => [
                    'sku' => 'CLR-BR-MOSS',
                    'color_name' => 'Rêu trầm',
                    'color_hex' => '#354033',
                    'price' => 1690000,
                    'compare_at_price' => null,
                    'stock_quantity' => 8,
                    'weight_grams' => 3000,
                ],
                'image' => [
                    'path' => 'images/catalog/moi2.png',
                    'alt_text' => 'Đèn bàn Bình Rêu thân gốm xanh rêu và chụp linen màu kem',
                ],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'cong-trang',
                'name' => 'Cổng Trăng',
                'short_description' => 'Quả cầu opal nằm gọn trong khung gỗ cong như một vầng trăng.',
                'description' => 'Cổng Trăng kết hợp khung gỗ óc chó, quả cầu opal và đế đá tối màu để tạo một điểm sáng có chiều sâu cho bàn console hoặc góc đọc.',
                'material' => 'Gỗ óc chó, thủy tinh opal, kim loại màu đồng và đế đá',
                'dimensions' => 'Rộng 28 cm × cao 48 cm',
                'published_minutes_ago' => 15,
                'variant' => [
                    'sku' => 'CLR-CT-WALNUT',
                    'color_name' => 'Óc chó & opal',
                    'color_hex' => '#5B3826',
                    'price' => 2090000,
                    'compare_at_price' => 2290000,
                    'stock_quantity' => 4,
                    'weight_grams' => 3500,
                ],
                'image' => [
                    'path' => 'images/catalog/moi3.png',
                    'alt_text' => 'Đèn trang trí Cổng Trăng với cầu opal trong khung gỗ óc chó',
                ],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'tho-om-trang',
                'name' => 'Thỏ Ôm Trăng',
                'short_description' => 'Chú thỏ nhỏ ôm quả cầu sáng ấm cho những giấc ngủ dịu.',
                'description' => 'Thỏ Ôm Trăng mang hình dáng mềm mại, ánh sáng được giữ trong quả cầu mờ để tạo một điểm sáng thân thiện trên bàn cạnh giường của bé.',
                'material' => 'Silicone mềm, nhựa ABS và đèn LED ánh vàng',
                'dimensions' => 'Rộng 19 cm × cao 25 cm',
                'published_minutes_ago' => 20,
                'variant' => [
                    'sku' => 'CLR-TOT-CREAM',
                    'color_name' => 'Kem sữa',
                    'color_hex' => '#EFE4D2',
                    'price' => 590000,
                    'compare_at_price' => null,
                    'stock_quantity' => 12,
                    'weight_grams' => 700,
                ],
                'image' => [
                    'path' => 'images/catalog/moi4.png',
                    'alt_text' => 'Đèn ngủ Thỏ Ôm Trăng màu kem ôm quả cầu phát sáng',
                ],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'nam-hong',
                'name' => 'Nấm Hồng',
                'short_description' => 'Chiếc nấm nhỏ tông hồng phấn với quầng sáng tròn êm mắt.',
                'description' => 'Nấm Hồng là mẫu đèn ngủ gọn nhẹ với nút điều khiển phía trước, phù hợp đặt trên bàn đầu giường hoặc góc nhỏ cần ánh sáng dịu.',
                'material' => 'Nhựa ABS hoàn thiện mờ, chụp tán sáng và đèn LED',
                'dimensions' => 'Rộng 18 cm × cao 23 cm',
                'published_minutes_ago' => 25,
                'variant' => [
                    'sku' => 'CLR-NAMH-BLUSH',
                    'color_name' => 'Hồng phấn',
                    'color_hex' => '#DCA996',
                    'price' => 520000,
                    'compare_at_price' => null,
                    'stock_quantity' => 14,
                    'weight_grams' => 600,
                ],
                'image' => [
                    'path' => 'images/catalog/moi5.png',
                    'alt_text' => 'Đèn ngủ Nấm Hồng tông hồng phấn với chụp sáng hình nấm',
                ],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'nu-som',
                'name' => 'Nụ Sớm',
                'short_description' => 'Nụ hoa opal hé sáng trên thân đồng và đế hồng đất.',
                'description' => 'Nụ Sớm tạo một điểm sáng thanh mảnh cho bàn trang điểm hoặc kệ nhỏ, với các cánh opal ôm lấy nguồn sáng và thân đồng mềm mại.',
                'material' => 'Thủy tinh opal, kim loại hoàn thiện đồng và đế nhựa phủ mờ',
                'dimensions' => 'Rộng 18 cm × cao 30 cm',
                'published_minutes_ago' => 30,
                'variant' => [
                    'sku' => 'CLR-NS-BLUSH',
                    'color_name' => 'Hồng đất & opal',
                    'color_hex' => '#C99278',
                    'price' => 790000,
                    'compare_at_price' => 850000,
                    'stock_quantity' => 9,
                    'weight_grams' => 900,
                ],
                'image' => [
                    'path' => 'images/catalog/moi6.png',
                    'alt_text' => 'Đèn trang trí Nụ Sớm với cánh opal, thân đồng và đế hồng đất',
                ],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'da-reu',
                'name' => 'Đá Rêu',
                'short_description' => 'Những khối bo tròn xếp lớp với sắc olive và ánh opal ấm.',
                'description' => 'Đá Rêu lấy cảm hứng từ những viên sỏi xếp cân bằng, phối thân olive trầm và hai lớp chụp sáng bo tròn cho góc nghỉ hiện đại.',
                'material' => 'Gốm phủ mờ, thủy tinh opal và chi tiết kim loại màu đồng',
                'dimensions' => 'Rộng 24 cm × cao 31 cm',
                'published_minutes_ago' => 35,
                'variant' => [
                    'sku' => 'CLR-DR-OLIVE',
                    'color_name' => 'Olive & kem đá',
                    'color_hex' => '#5C624B',
                    'price' => 1190000,
                    'compare_at_price' => null,
                    'stock_quantity' => 7,
                    'weight_grams' => 2200,
                ],
                'image' => [
                    'path' => 'images/catalog/moi7.png',
                    'alt_text' => 'Đèn bàn Đá Rêu với thân olive và các khối opal bo tròn xếp lớp',
                ],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'long-am',
                'name' => 'Lồng Ấm',
                'short_description' => 'Khung gỗ có quai xách ôm lấy chụp linen sáng dịu.',
                'description' => 'Lồng Ấm là mẫu đèn đặt bàn có dáng xách tay, kết hợp gỗ sẫm, quai kim loại màu đồng và chụp linen để mang ánh sáng đến nhiều góc nhỏ trong nhà.',
                'material' => 'Gỗ óc chó, linen, kim loại hoàn thiện đồng và đèn LED',
                'dimensions' => 'Rộng 22 cm × cao 39 cm',
                'published_minutes_ago' => 40,
                'variant' => [
                    'sku' => 'CLR-LA-WALNUT',
                    'color_name' => 'Gỗ óc chó',
                    'color_hex' => '#65422D',
                    'price' => 1490000,
                    'compare_at_price' => 1590000,
                    'stock_quantity' => 6,
                    'weight_grams' => 1800,
                ],
                'image' => [
                    'path' => 'images/catalog/moi8.png',
                    'alt_text' => 'Đèn bàn Lồng Ấm khung gỗ óc chó, quai đồng và chụp linen',
                ],
            ],
        ];
    }
}
