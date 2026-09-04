<?php

namespace Tests\Feature;

use App\Modules\Catalog\Actions\GetStorefrontHomeAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seed_data_is_visible_on_the_storefront(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Đèn dịu')
            ->assertSee('Danh mục Clare')
            ->assertSee('Ru Đêm')
            ->assertSee('Thảo Mộc')
            ->assertSee('Hoàng Hôn')
            ->assertSee('images/catalog/banner.png')
            ->assertSee('data-brand-banner', false)
            ->assertSee('data-reveal-group', false)
            ->assertSee('data-lamp-scene', false)
            ->assertSee('data-lamp-toggle', false)
            ->assertSee('Kéo dây để bật đèn')
            ->assertSee('id="main-content" tabindex="-1"', false)
            ->assertSee('data-mobile-menu', false)
            ->assertSee('aria-controls="mobile-navigation"', false)
            ->assertSee('data-header-search-form', false)
            ->assertSee('id="header-search-panel"', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('aria-expanded="false" data-search-open', false)
            ->assertSee('Tìm đèn bàn, opal, gỗ');

        $this->get('/collections/den-ban')
            ->assertOk()
            ->assertSee('class="catalog-collection-page"', false)
            ->assertSee('Khám phá theo góc nhà')
            ->assertSee('/collections/den-ngu-de-thuong', false)
            ->assertSee('data-reveal-group', false)
            ->assertSee('Đèn bàn')
            ->assertSee('Ru Đêm')
            ->assertSee('Thảo Mộc')
            ->assertDontSee('Hoàng Hôn');

        $this->get('/products/ru-dem')
            ->assertOk()
            ->assertSee('class="catalog-product-detail-page"', false)
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-gallery-thumbnail', false)
            ->assertSee('data-image-url=', false)
            ->assertSee('Đỏ vang')
            ->assertSee('Kem ấm')
            ->assertSee('2.490.000 VND')
            ->assertSee('data-add-cart-form', false)
            ->assertSee('data-cart-feedback', false);
    }

    public function test_future_products_are_not_available_on_the_storefront(): void
    {
        $category = Category::query()->create([
            'name' => 'Đèn thử nghiệm',
            'slug' => 'den-thu-nghiem',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Ngày Mai',
            'slug' => 'ngay-mai',
            'is_active' => true,
            'is_featured' => true,
            'published_at' => now()->addDay(),
        ]);

        $product->variants()->create([
            'sku' => 'CLR-FUTURE-01',
            'color_name' => 'Kem',
            'price' => 1000000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->get('/products/ngay-mai')->assertNotFound();
        $this->get('/collections/den-thu-nghiem')
            ->assertOk()
            ->assertDontSee('Ngày Mai');
    }

    public function test_inactive_categories_are_not_available_on_the_storefront(): void
    {
        Category::query()->create([
            'name' => 'Đã ẩn',
            'slug' => 'da-an',
            'is_active' => false,
        ]);

        $this->get('/collections/da-an')->assertNotFound();
    }

    public function test_search_returns_only_matching_published_products(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/search?q=opal')
            ->assertOk()
            ->assertSee('class="catalog-search-page"', false)
            ->assertSee('Kết quả cho')
            ->assertSee('“opal”')
            ->assertSee('data-search-categories', false)
            ->assertSee('Ru Đêm')
            ->assertSee('Hoàng Hôn')
            ->assertDontSee('Thảo Mộc');
    }

    public function test_full_catalog_can_be_filtered_by_category(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/products')
            ->assertOk()
            ->assertSee('Chiếc đèn khiến bạn')
            ->assertSee('data-products-collage', false)
            ->assertSee('id="catalog-category-filter"', false)
            ->assertSee('data-catalog-filter-group', false)
            ->assertSee('Tất cả đèn (27)')
            ->assertDontSee('— Kiểu dáng &amp; cấu tạo')
            ->assertDontSee('Đèn chùm (0)')
            ->assertSee('aria-current="page"', false)
            ->assertSee('<strong>27</strong> sản phẩm', false)
            ->assertSee('Phân trang sản phẩm')
            ->assertSee('Ru Đêm')
            ->assertSee('Thảo Mộc')
            ->assertSee('Hoàng Hôn');

        $this->get('/products?category=den-tuong')
            ->assertOk()
            ->assertSee('Đèn tường')
            ->assertSee('1 đang chọn')
            ->assertSee('value="den-tuong" selected', false)
            ->assertSee('Gỡ bộ lọc')
            ->assertSee('Hoàng Hôn')
            ->assertDontSee('Thảo Mộc');
    }

    public function test_supplemental_catalog_seed_data_is_visible_with_sellable_variants(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(27, Product::query()->count());
        $this->assertSame(23, Category::query()->count());
        $this->assertDatabaseCount('product_variants', 30);
        $this->assertDatabaseCount('product_images', 33);
        $this->assertSame(3, Product::query()->where('slug', 'may-nam')->firstOrFail()->images()->count());

        $this->get('/products?category=den-ngu-de-thuong')
            ->assertOk()
            ->assertSee('Mây Tulip')
            ->assertSee('Vịt Nhỏ')
            ->assertDontSee('Ru Đêm');
    }

    public function test_new_arrival_catalog_images_have_sellable_products(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertDatabaseCount('products', 27);
        $this->assertDatabaseCount('product_variants', 30);
        $this->assertDatabaseCount('product_images', 33);

        $newProducts = Product::query()
            ->whereIn('slug', [
                'bac-sang',
                'binh-reu',
                'cong-trang',
                'tho-om-trang',
                'nam-hong',
                'nu-som',
                'da-reu',
                'long-am',
            ])
            ->with(['variants', 'images'])
            ->get();

        $this->assertCount(8, $newProducts);
        $this->assertTrue($newProducts->every(fn (Product $product) => $product->variants->count() === 1));
        $this->assertTrue($newProducts->every(fn (Product $product) => $product->images->count() === 1));

        foreach (range(1, 8) as $imageNumber) {
            $this->assertFileExists(public_path("images/catalog/moi{$imageNumber}.png"));
        }

        $this->get('/products/bac-sang')
            ->assertOk()
            ->assertSeeText('Bậc Sáng')
            ->assertSee('images/catalog/moi1.png');

        $this->get('/products/tho-om-trang')
            ->assertOk()
            ->assertSeeText('Thỏ Ôm Trăng')
            ->assertSee('images/catalog/moi4.png');
    }

    public function test_home_collection_cards_have_existing_cover_images(): void
    {
        $this->seed(CatalogSeeder::class);

        $categories = app(GetStorefrontHomeAction::class)->execute()['categories'];

        $this->assertCount(6, $categories);

        foreach ($categories as $category) {
            $this->assertNotEmpty($category->image_path, "Danh mục {$category->slug} chưa có ảnh đại diện.");
            $this->assertFileExists(public_path($category->image_path));
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('images/catalog/moi1.png')
            ->assertSee('images/catalog/moi3.png')
            ->assertSee('images/catalog/den-ban-thao-moc-ngu.png')
            ->assertSee('images/catalog/den-ban-ru-dem-ngu.png')
            ->assertDontSee('<span class="image-placeholder"', false);
    }
}
