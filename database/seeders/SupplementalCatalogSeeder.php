<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplementalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = [
                'den-ban' => Category::query()
                    ->where('slug', 'den-ban')
                    ->firstOrFail(),
                'den-trang-tri' => Category::query()->updateOrCreate(
                    ['slug' => 'den-trang-tri'],
                    [
                        'name' => 'Đèn trang trí',
                        'description' => 'Những mẫu đèn tạo điểm nhấn dịu dàng cho bàn nhỏ, góc đọc và không gian nghỉ ngơi.',
                        'image_path' => 'images/catalog/imgi_256_bd408ed0cb2d36c7e4c09a0dfee47538.jpg',
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                ),
                'den-ngu-de-thuong' => Category::query()->updateOrCreate(
                    ['slug' => 'den-ngu-de-thuong'],
                    [
                        'name' => 'Đèn ngủ dễ thương',
                        'description' => 'Những quầng sáng nhỏ, vui mắt và vừa vặn cho góc nghỉ của bé hoặc bàn cạnh giường.',
                        'image_path' => 'images/catalog/imgi_638_3f7c9cdc15ccf341a4a6661d2b5825c8.jpg',
                        'is_active' => true,
                        'sort_order' => 40,
                    ],
                ),
            ];

            foreach ($this->products() as $product) {
                $category = $categories[$product['category']];
                $productModel = Product::query()->updateOrCreate(
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
                        'published_at' => now()->subHours($product['published_hours_ago']),
                    ],
                );
                $productModel->categories()->syncWithoutDetaching([$category->getKey()]);

                $productModel->variants()->updateOrCreate(
                    ['sku' => $product['variant']['sku']],
                    [
                        ...$product['variant'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                );

                foreach ($product['images'] as $sortOrder => $image) {
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
        });
    }

    private function products(): array
    {
        return [
            [
                'category' => 'den-trang-tri',
                'slug' => 'vuon-dom-dom',
                'name' => 'Vườn Đom Đóm',
                'short_description' => 'Một khu vườn hoa thu nhỏ với những điểm sáng vàng ấm.',
                'description' => 'Vườn Đom Đóm đặt hoa và ánh sáng trong một khối kính nhỏ, phù hợp để làm dịu mặt bàn cạnh giường hoặc góc đọc.',
                'material' => 'Kính, hoa trang trí và đèn LED ánh vàng',
                'dimensions' => 'Rộng 18 cm × cao 24 cm',
                'published_hours_ago' => 1,
                'variant' => [
                    'sku' => 'CLR-VD-AMBER',
                    'color_name' => 'Hổ phách ấm',
                    'color_hex' => '#D9A85E',
                    'price' => 790000,
                    'compare_at_price' => null,
                    'stock_quantity' => 7,
                    'weight_grams' => 1100,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_13_37cdc744f46632ea6c90457852469cdd.jpg',
                    'alt_text' => 'Đèn trang trí Vườn Đom Đóm với hoa phát sáng trong khối kính',
                ]],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'goc-mo-som',
                'name' => 'Góc Mơ Sớm',
                'short_description' => 'Đèn bàn nhỏ cho góc học tập với lớp sáng hồng dịu.',
                'description' => 'Góc Mơ Sớm tạo một lớp sáng vừa phải trên mặt bàn, dành cho những buổi đọc nhẹ hoặc viết vài dòng trước khi ngủ.',
                'material' => 'Nhựa ABS hoàn thiện mờ, đèn LED',
                'dimensions' => 'Rộng 20 cm × cao 31 cm',
                'published_hours_ago' => 2,
                'variant' => [
                    'sku' => 'CLR-GMS-ROSE',
                    'color_name' => 'Hồng phấn',
                    'color_hex' => '#E7B2C0',
                    'price' => 690000,
                    'compare_at_price' => 750000,
                    'stock_quantity' => 9,
                    'weight_grams' => 800,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_199_907db2f0e36834a97b514c74c1a6fdfc.jpg',
                    'alt_text' => 'Đèn bàn Góc Mơ Sớm trong không gian bàn học tông hồng',
                ]],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'vanh-khuyet',
                'name' => 'Vành Khuyết',
                'short_description' => 'Dáng đèn cong gọn gàng, soi vừa đủ cho bàn nhỏ.',
                'description' => 'Vành Khuyết là một mẫu đèn bàn tối giản có chụp sáng hướng xuống, phù hợp cho bàn đọc và góc làm việc buổi tối.',
                'material' => 'Kim loại sơn mờ, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 19 cm × cao 37 cm',
                'published_hours_ago' => 3,
                'variant' => [
                    'sku' => 'CLR-VK-CREAM',
                    'color_name' => 'Kem sáng',
                    'color_hex' => '#E9DFCC',
                    'price' => 590000,
                    'compare_at_price' => null,
                    'stock_quantity' => 11,
                    'weight_grams' => 950,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_224_6d839f4c2c058ccb8a300ca23b64a166.jpg',
                    'alt_text' => 'Đèn bàn Vành Khuyết màu kem trên mặt bàn gỗ sáng',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'trang-sen',
                'name' => 'Trăng Sen',
                'short_description' => 'Vầng trăng tròn ôm lấy một cụm hoa sen phát sáng.',
                'description' => 'Trăng Sen là điểm nhấn ánh sáng cho kệ thấp hoặc bàn trang trí, kết hợp bề mặt trăng tròn và những bông sen tông ấm.',
                'material' => 'Gỗ hoàn thiện sơn mờ, acrylic và đèn LED',
                'dimensions' => 'Rộng 31 cm × cao 36 cm',
                'published_hours_ago' => 4,
                'variant' => [
                    'sku' => 'CLR-TS-GOLD',
                    'color_name' => 'Vàng nguyệt',
                    'color_hex' => '#D4A94F',
                    'price' => 1290000,
                    'compare_at_price' => 1390000,
                    'stock_quantity' => 5,
                    'weight_grams' => 2600,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_256_bd408ed0cb2d36c7e4c09a0dfee47538.jpg',
                    'alt_text' => 'Đèn trang trí Trăng Sen với hoa phát sáng trước vầng trăng tròn',
                ]],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'may-tulip',
                'name' => 'Mây Tulip',
                'short_description' => 'Đèn ngủ hình mây với một vườn tulip nhỏ bên trong.',
                'description' => 'Mây Tulip tạo ánh sáng dịu cho đầu giường, với hình dáng mềm mại và cụm hoa nhỏ mang lại cảm giác vui mắt.',
                'material' => 'Acrylic, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 24 cm × cao 18 cm',
                'published_hours_ago' => 5,
                'variant' => [
                    'sku' => 'CLR-MT-PEACH',
                    'color_name' => 'Đào nhạt',
                    'color_hex' => '#F1B8A9',
                    'price' => 520000,
                    'compare_at_price' => null,
                    'stock_quantity' => 12,
                    'weight_grams' => 650,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_34_50554a7c3439d3d1da81ef7c25304113.jpg',
                    'alt_text' => 'Đèn ngủ Mây Tulip với hoa phát sáng bên trong',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'binh-tulip',
                'name' => 'Bình Tulip',
                'short_description' => 'Một bó tulip trắng phát sáng, đặt gọn trong bình thủy tinh.',
                'description' => 'Bình Tulip mang dáng hoa tươi vào không gian nghỉ ngơi bằng một lớp sáng vàng nhẹ, phù hợp cho bàn cạnh giường và bàn trang điểm.',
                'material' => 'Thủy tinh, hoa trang trí và đèn LED',
                'dimensions' => 'Rộng 19 cm × cao 32 cm',
                'published_hours_ago' => 6,
                'variant' => [
                    'sku' => 'CLR-BT-IVORY',
                    'color_name' => 'Trắng ngà',
                    'color_hex' => '#F3E8D8',
                    'price' => 620000,
                    'compare_at_price' => null,
                    'stock_quantity' => 8,
                    'weight_grams' => 1200,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_348_21b1fed31fe1a238f49cd34faa39a1c3.jpg',
                    'alt_text' => 'Đèn trang trí Bình Tulip với hoa trắng phát sáng trong bình',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'tulip-tim',
                'name' => 'Tulip Tím',
                'short_description' => 'Những nụ tulip tím lên sáng trên đế gỗ nhỏ.',
                'description' => 'Tulip Tím là mẫu đèn trang trí mang tông màu trầm hơn, dành cho một góc bàn cần chút ánh sáng và sắc màu.',
                'material' => 'Acrylic, gỗ và đèn LED',
                'dimensions' => 'Rộng 20 cm × cao 27 cm',
                'published_hours_ago' => 7,
                'variant' => [
                    'sku' => 'CLR-TT-VIOLET',
                    'color_name' => 'Tím chiều',
                    'color_hex' => '#8C5D9B',
                    'price' => 720000,
                    'compare_at_price' => 780000,
                    'stock_quantity' => 6,
                    'weight_grams' => 1000,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_36_7db780e4033219e7f0bcc16c98aae5e5.jpg',
                    'alt_text' => 'Đèn trang trí Tulip Tím với cụm hoa phát sáng trên đế tròn',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'nhanh-hong',
                'name' => 'Nhành Hồng',
                'short_description' => 'Cành hoa hồng uốn cong thành một vầng sáng nhẹ.',
                'description' => 'Nhành Hồng là đèn trang trí đặt bàn với khung hoa mảnh, phù hợp khi bạn muốn giữ lại một góc dịu dàng trong căn phòng.',
                'material' => 'Kim loại sơn phủ, hoa trang trí và đèn LED',
                'dimensions' => 'Rộng 26 cm × cao 35 cm',
                'published_hours_ago' => 8,
                'variant' => [
                    'sku' => 'CLR-NH-ROSE',
                    'color_name' => 'Hồng phai',
                    'color_hex' => '#DFA8AF',
                    'price' => 680000,
                    'compare_at_price' => null,
                    'stock_quantity' => 8,
                    'weight_grams' => 1100,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_363_446d39b296b8e8d568fc0f3e46a1a83a.jpg',
                    'alt_text' => 'Đèn trang trí Nhành Hồng với cành hoa phát sáng uốn cong',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'hop-tulip',
                'name' => 'Hộp Tulip',
                'short_description' => 'Một hộp hoa tulip phát sáng cho bàn cạnh giường.',
                'description' => 'Hộp Tulip có kiểu dáng hộp trong suốt, làm nổi bật các bông hoa nhỏ tỏa sáng dịu vào buổi tối.',
                'material' => 'Acrylic, hoa trang trí và đèn LED',
                'dimensions' => 'Rộng 18 cm × cao 22 cm',
                'published_hours_ago' => 9,
                'variant' => [
                    'sku' => 'CLR-HT-BLUSH',
                    'color_name' => 'Hồng tulip',
                    'color_hex' => '#E0A5B3',
                    'price' => 540000,
                    'compare_at_price' => null,
                    'stock_quantity' => 10,
                    'weight_grams' => 800,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_38_81e10b53abc49bc0abd6705cd9e96c12.jpg',
                    'alt_text' => 'Đèn trang trí Hộp Tulip với hoa hồng phát sáng trong hộp trong suốt',
                ]],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'cu-dao-choi',
                'name' => 'Cú Dạo Chơi',
                'short_description' => 'Đèn ngủ tạo hình chú cú nhỏ cho bàn cạnh giường.',
                'description' => 'Cú Dạo Chơi là một điểm sáng nhỏ có hình dáng thân thiện, phù hợp cho góc nghỉ của bé hoặc không gian cần sự vui mắt.',
                'material' => 'Silicone mềm, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 15 cm × cao 23 cm',
                'published_hours_ago' => 10,
                'variant' => [
                    'sku' => 'CLR-CD-MUSTARD',
                    'color_name' => 'Vàng mù tạt',
                    'color_hex' => '#D7A83E',
                    'price' => 480000,
                    'compare_at_price' => null,
                    'stock_quantity' => 9,
                    'weight_grams' => 600,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_385_142af44a67cfab1cde33129cfe74024e.jpg',
                    'alt_text' => 'Đèn ngủ Cú Dạo Chơi tạo hình chú cú nhỏ tông vàng',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'buom-trang',
                'name' => 'Bướm Trăng',
                'short_description' => 'Một vầng trăng nhỏ với cánh bướm đổi sắc ánh sáng.',
                'description' => 'Bướm Trăng tạo nên một điểm nhìn lấp lánh trên mặt bàn, với lớp acrylic phản chiếu ánh sáng xanh tím dịu.',
                'material' => 'Acrylic, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 22 cm × cao 29 cm',
                'published_hours_ago' => 11,
                'variant' => [
                    'sku' => 'CLR-BT-LILAC',
                    'color_name' => 'Tím nguyệt',
                    'color_hex' => '#9E9ADD',
                    'price' => 750000,
                    'compare_at_price' => 820000,
                    'stock_quantity' => 7,
                    'weight_grams' => 900,
                ],
                'images' => [
                    [
                        'path' => 'images/catalog/imgi_43_7807b98d35ee42f8cfd9ceab932a7f7e.jpg',
                        'alt_text' => 'Đèn trang trí Bướm Trăng với cánh bướm phát sáng trên đế tròn',
                    ],
                    [
                        'path' => 'images/catalog/imgi_64_7807b98d35ee42f8cfd9ceab932a7f7e.jpg',
                        'alt_text' => 'Góc nhìn thứ hai của đèn trang trí Bướm Trăng',
                    ],
                ],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'may-nam',
                'name' => 'Mây Nấm',
                'short_description' => 'Đèn ngủ hình mây với một cụm nấm phát sáng nhiều màu.',
                'description' => 'Mây Nấm giữ ánh sáng ở mức dịu cho đêm khuya, với các cụm nấm nhỏ tạo cảm giác vui tươi trên đầu giường.',
                'material' => 'Acrylic, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 26 cm × cao 19 cm',
                'published_hours_ago' => 12,
                'variant' => [
                    'sku' => 'CLR-MN-AQUA',
                    'color_name' => 'Xanh mây',
                    'color_hex' => '#94D7E0',
                    'price' => 690000,
                    'compare_at_price' => null,
                    'stock_quantity' => 11,
                    'weight_grams' => 750,
                ],
                'images' => [
                    [
                        'path' => 'images/catalog/imgi_48_be5bf8179c4b3d1d0d1e4f4d7931c0f6.jpg',
                        'alt_text' => 'Đèn ngủ Mây Nấm với cụm nấm xanh phát sáng',
                    ],
                    [
                        'path' => 'images/catalog/imgi_60_75d72e74a002c21e58f30b7528a7bed1.jpg',
                        'alt_text' => 'Đèn ngủ Mây Nấm với nhiều lựa chọn màu ánh sáng',
                    ],
                    [
                        'path' => 'images/catalog/imgi_62_761201df639d47304058bcce108f2604.jpg',
                        'alt_text' => 'Góc nhìn thứ ba của đèn ngủ Mây Nấm',
                    ],
                ],
            ],
            [
                'category' => 'den-ban',
                'slug' => 'doc-dem',
                'name' => 'Đọc Đêm',
                'short_description' => 'Dáng đèn bàn cổ điển, tỏa sáng ấm cho trang sách.',
                'description' => 'Đọc Đêm có thân kim loại nhỏ gọn và chụp vải xếp nếp, mang lại một quầng sáng tập trung cho bàn đầu giường.',
                'material' => 'Kim loại hoàn thiện đồng, chụp vải và đèn LED',
                'dimensions' => 'Rộng 23 cm × cao 40 cm',
                'published_hours_ago' => 13,
                'variant' => [
                    'sku' => 'CLR-DD-BRASS',
                    'color_name' => 'Đồng cổ',
                    'color_hex' => '#A87B3B',
                    'price' => 890000,
                    'compare_at_price' => null,
                    'stock_quantity' => 6,
                    'weight_grams' => 1700,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_496_db95d74741033a6e818e8bb85afb0c1f.jpg',
                    'alt_text' => 'Đèn bàn Đọc Đêm với chụp vải xếp nếp tỏa ánh sáng vàng',
                ]],
            ],
            [
                'category' => 'den-trang-tri',
                'slug' => 'xich-du-hoa',
                'name' => 'Xích Đu Hoa',
                'short_description' => 'Vòm hoa nhỏ và chiếc xích đu tạo một góc sáng mơ mộng.',
                'description' => 'Xích Đu Hoa là mẫu đèn trang trí có dáng vòm và hoa nở, được chọn cho kệ thấp hoặc góc phòng cần thêm chất thơ.',
                'material' => 'Gỗ, kim loại sơn phủ, hoa trang trí và đèn LED',
                'dimensions' => 'Rộng 28 cm × cao 38 cm',
                'published_hours_ago' => 14,
                'variant' => [
                    'sku' => 'CLR-XDH-BLOSSOM',
                    'color_name' => 'Hồng hoa',
                    'color_hex' => '#E8A9B7',
                    'price' => 780000,
                    'compare_at_price' => 850000,
                    'stock_quantity' => 5,
                    'weight_grams' => 1500,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_516_bdc6c7bbc2b12f30ede2004d10c72afa.jpg',
                    'alt_text' => 'Đèn trang trí Xích Đu Hoa với vòm hoa hồng và xích đu nhỏ',
                ]],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'nam-do',
                'name' => 'Nấm Đỏ',
                'short_description' => 'Một cụm nấm đỏ nhỏ phát sáng cho đêm khuya.',
                'description' => 'Nấm Đỏ có dáng tròn vui mắt và ánh sáng nhẹ, thích hợp đặt cạnh giường hoặc làm đèn trang trí cho góc nhỏ.',
                'material' => 'Nhựa ABS, acrylic và đèn LED',
                'dimensions' => 'Rộng 17 cm × cao 21 cm',
                'published_hours_ago' => 15,
                'variant' => [
                    'sku' => 'CLR-ND-SCARLET',
                    'color_name' => 'Đỏ nấm',
                    'color_hex' => '#D64937',
                    'price' => 420000,
                    'compare_at_price' => null,
                    'stock_quantity' => 14,
                    'weight_grams' => 550,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_52_6176ed5b3392677b272056a1135d1394.jpg',
                    'alt_text' => 'Đèn ngủ Nấm Đỏ với cụm nấm phát sáng trên đế tròn',
                ]],
            ],
            [
                'category' => 'den-ngu-de-thuong',
                'slug' => 'vit-nho',
                'name' => 'Vịt Nhỏ',
                'short_description' => 'Đèn ngủ hình vịt với ánh sáng êm bên trong thân tròn.',
                'description' => 'Vịt Nhỏ mang đến một quầng sáng gần gũi trên bàn cạnh giường, với hình dáng mềm mại và tông vàng ấm.',
                'material' => 'Silicone mềm, nhựa ABS và đèn LED',
                'dimensions' => 'Rộng 16 cm × cao 18 cm',
                'published_hours_ago' => 16,
                'variant' => [
                    'sku' => 'CLR-VN-SUNNY',
                    'color_name' => 'Vàng nắng',
                    'color_hex' => '#F2C94C',
                    'price' => 350000,
                    'compare_at_price' => null,
                    'stock_quantity' => 16,
                    'weight_grams' => 450,
                ],
                'images' => [[
                    'path' => 'images/catalog/imgi_638_3f7c9cdc15ccf341a4a6661d2b5825c8.jpg',
                    'alt_text' => 'Đèn ngủ Vịt Nhỏ hình chú vịt vàng phát sáng',
                ]],
            ],
        ];
    }
}
