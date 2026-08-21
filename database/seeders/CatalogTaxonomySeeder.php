<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $lampRoot = $this->category('den', 'Đèn', null, 1, 'Khám phá đèn theo kiểu dáng, không gian và công năng sử dụng.');
            $styleRoot = $this->category('kieu-dang-cau-tao', 'Kiểu dáng & cấu tạo', $lampRoot, 10);
            $spaceRoot = $this->category('khong-gian-su-dung', 'Không gian sử dụng', $lampRoot, 20);
            $useRoot = $this->category('cong-nang-ung-dung', 'Công năng & ứng dụng', $lampRoot, 30);

            $styleCategories = [
                ['den-chum', 'Đèn chùm'],
                ['den-tha', 'Đèn thả'],
                ['den-op-tran', 'Đèn ốp trần'],
                ['den-am-tran', 'Đèn âm trần'],
                ['den-tuong', 'Đèn tường'],
                ['den-roi-den-ray', 'Đèn rọi / Đèn ray'],
                ['den-ban', 'Đèn bàn'],
                ['den-cay', 'Đèn cây'],
                ['den-led-day-den-tuyp', 'Đèn LED dây & Đèn tuýp'],
            ];
            $spaceCategories = [
                ['den-phong-khach', 'Đèn phòng khách'],
                ['den-phong-ngu', 'Đèn phòng ngủ'],
                ['den-phong-bep-phong-an', 'Đèn phòng bếp / Phòng ăn'],
                ['den-phong-tam', 'Đèn phòng tắm'],
                ['den-ngoai-troi-san-vuon', 'Đèn ngoài trời / Sân vườn'],
            ];
            $useCategories = [
                ['den-trang-tri', 'Đèn trang trí'],
                ['den-chieu-sang-tong-the', 'Đèn chiếu sáng tổng thể'],
                ['den-cong-nghiep-thuong-mai', 'Đèn công nghiệp & Thương mại'],
                ['den-thong-minh', 'Đèn thông minh'],
            ];

            foreach ($styleCategories as $index => [$slug, $name]) {
                $this->category($slug, $name, $styleRoot, ($index + 1) * 10);
            }

            foreach ($spaceCategories as $index => [$slug, $name]) {
                $this->category($slug, $name, $spaceRoot, ($index + 1) * 10);
            }

            foreach ($useCategories as $index => [$slug, $name]) {
                $this->category($slug, $name, $useRoot, ($index + 1) * 10);
            }

            $cuteLamps = Category::query()->where('slug', 'den-ngu-de-thuong')->first();
            $bedroom = Category::query()->where('slug', 'den-phong-ngu')->first();

            if ($cuteLamps && $bedroom) {
                $cuteLamps->update(['parent_id' => $bedroom->getKey()]);
            }

            $brand = Brand::query()->updateOrCreate(
                ['slug' => 'clare'],
                [
                    'name' => 'Clare',
                    'description' => 'Những mẫu đèn được Clare chọn cho không gian sống dịu và ấm.',
                    'country' => 'Việt Nam',
                    'is_active' => true,
                    'sort_order' => 10,
                ],
            );

            Product::query()->whereNull('brand_id')->update(['brand_id' => $brand->getKey()]);
            Product::query()->with('category')->each(function (Product $product): void {
                if ($product->category_id !== null) {
                    $product->categories()->syncWithoutDetaching([$product->category_id]);
                }
            });

            $this->seedAttributes();
            $this->assignKnownAttributes();
        });
    }

    private function category(string $slug, string $name, ?Category $parent, int $sortOrder, ?string $description = null): Category
    {
        $category = Category::query()->firstOrNew(['slug' => $slug]);
        $category->fill([
            'parent_id' => $parent?->getKey(),
            'name' => $name,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        if ($description !== null || ! $category->exists) {
            $category->description = $description;
        }

        $category->save();

        return $category;
    }

    private function seedAttributes(): void
    {
        $definitions = [
            ['cong-suat', 'Công suất', 'W', 'number', ['5W', '7W', '9W', '12W', '18W']],
            ['mau-anh-sang', 'Màu ánh sáng', null, 'color', ['Vàng ấm', 'Trung tính', 'Trắng']],
            ['nhiet-do-mau', 'Nhiệt độ màu', 'K', 'number', ['2700K', '3000K', '4000K', '6500K']],
            ['dien-ap', 'Điện áp', 'V', 'number', ['12V', '24V', '220V']],
            ['chat-lieu', 'Chất liệu', null, 'select', ['Nhôm', 'Đồng', 'Pha lê', 'Thép', 'Gốm', 'Thủy tinh', 'Linen', 'Kim loại']],
            ['kieu-dang', 'Kiểu dáng', null, 'select', ['Đèn chùm', 'Đèn thả', 'Đèn ốp trần', 'Đèn âm trần', 'Đèn tường', 'Đèn rọi / Đèn ray', 'Đèn bàn', 'Đèn cây']],
            ['khong-gian', 'Không gian sử dụng', null, 'select', ['Phòng khách', 'Phòng ngủ', 'Phòng bếp / Phòng ăn', 'Phòng tắm', 'Ngoài trời / Sân vườn']],
            ['chi-so-ip', 'Chỉ số IP', null, 'select', ['IP20', 'IP44', 'IP54', 'IP65', 'IP67']],
        ];

        foreach ($definitions as $attributeIndex => [$slug, $name, $unit, $type, $values]) {
            $attribute = ProductAttribute::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'unit' => $unit,
                    'filter_type' => $type,
                    'is_filterable' => true,
                    'is_active' => true,
                    'sort_order' => ($attributeIndex + 1) * 10,
                ],
            );

            foreach ($values as $valueIndex => $label) {
                $numericValue = preg_match('/^([0-9.]+)/', $label, $matches) ? (float) $matches[1] : null;
                $attribute->values()->updateOrCreate(
                    ['slug' => Str::slug($label)],
                    [
                        'label' => $label,
                        'numeric_value' => $numericValue,
                        'sort_order' => ($valueIndex + 1) * 10,
                    ],
                );
            }
        }
    }

    private function assignKnownAttributes(): void
    {
        $materialAttribute = ProductAttribute::query()->where('slug', 'chat-lieu')->with('values')->first();
        $styleAttribute = ProductAttribute::query()->where('slug', 'kieu-dang')->with('values')->first();

        Product::query()->with('category')->each(function (Product $product) use ($materialAttribute, $styleAttribute): void {
            $valueIds = collect();
            $material = Str::lower((string) $product->material);

            foreach ($materialAttribute?->values ?? [] as $value) {
                if (str_contains($material, Str::lower($value->label))) {
                    $valueIds->push($value->getKey());
                }
            }

            if ($styleAttribute && $product->category) {
                $styleLabel = match ($product->category->slug) {
                    'den-ban' => 'Đèn bàn',
                    'den-tuong' => 'Đèn tường',
                    default => null,
                };
                $styleValue = $styleLabel === null ? null : $styleAttribute->values->firstWhere('label', $styleLabel);

                if ($styleValue) {
                    $valueIds->push($styleValue->getKey());
                }
            }

            $product->attributeValues()->syncWithoutDetaching($valueIds->unique()->all());
        });
    }
}
