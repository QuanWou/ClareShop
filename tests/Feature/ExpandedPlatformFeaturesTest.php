<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductReview;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Models\SiteSetting;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ExpandedPlatformFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_publish_a_rich_blog_post_with_related_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $category = BlogCategory::query()->create(['name' => 'Cẩm nang', 'slug' => 'cam-nang', 'is_active' => true]);
        $product = Product::query()->where('slug', 'ru-dem')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.blog.posts.store'), [
            'title' => 'Chọn ánh sáng dịu cho phòng ngủ',
            'slug' => 'chon-anh-sang-diu',
            'blog_category_id' => $category->getKey(),
            'excerpt' => 'Một hướng dẫn ngắn để căn phòng dịu hơn.',
            'content' => '<h2>Bắt đầu từ thói quen nghỉ ngơi</h2><p>Chọn ánh sáng ấm và vừa đủ cho buổi tối.</p>',
            'status' => 'published',
            'product_ids' => [$product->getKey()],
        ])->assertRedirect();

        $this->assertDatabaseHas('blog_posts', ['slug' => 'chon-anh-sang-diu', 'status' => 'published']);
        $this->get('/cam-hung/chon-anh-sang-diu')
            ->assertOk()
            ->assertSee('Bắt đầu từ thói quen nghỉ ngơi')
            ->assertSee('Ru Đêm');
    }

    public function test_only_a_customer_with_a_completed_order_can_submit_a_review_and_admin_can_approve_it(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $product = Product::query()->where('slug', 'ru-dem')->with('activeVariants')->firstOrFail();
        $order = $this->completedOrder($customer);
        $variant = $product->activeVariants->firstOrFail();
        $order->items()->create([
            'product_variant_id' => $variant->getKey(),
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'color_name' => $variant->color_name,
            'sku' => $variant->sku,
            'unit_price' => $variant->price,
            'quantity' => 1,
            'line_total' => $variant->price,
        ]);

        $this->actingAs($customer)->post(route('catalog.products.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Ánh sáng rất ấm',
            'comment' => 'Đèn dịu mắt, đóng gói cẩn thận và hợp phòng ngủ.',
        ])->assertRedirect();

        $review = ProductReview::query()->firstOrFail();
        $this->assertSame('pending', $review->status);

        $this->actingAs($admin)->patch(route('admin.reviews.update', $review), [
            'status' => 'approved',
        ])->assertRedirect();

        $this->get(route('catalog.products.show', $product))
            ->assertOk()
            ->assertSee('Ánh sáng rất ấm')
            ->assertSee('Đã mua hàng tại Clare');
    }

    public function test_customer_can_manage_multiple_addresses_and_choose_a_default(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $payload = [
            'label' => 'Nhà riêng',
            'recipient_name' => 'Nguyễn Minh An',
            'address_phone' => '0901234567',
            'address_line_1' => '12 Nguyễn Huệ',
            'ward' => 'Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'country_code' => 'VN',
        ];

        $this->actingAs($customer)->post(route('account.addresses.store'), $payload)->assertRedirect();
        $this->actingAs($customer)->post(route('account.addresses.store'), [
            ...$payload,
            'label' => 'Văn phòng',
            'address_line_1' => '88 Lê Lợi',
            'is_default' => 1,
        ])->assertRedirect();

        $this->assertSame(2, $customer->addresses()->count());
        $this->assertSame('Văn phòng', $customer->addresses()->where('is_default', true)->firstOrFail()->label);
        $this->actingAs($customer)->get(route('account.show'))->assertSee('88 Lê Lợi');
    }

    public function test_admin_settings_encrypt_secrets_and_apply_the_theme(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $payload = [
            ...config('site-settings.defaults'),
            'store_name' => 'CLARE TEST',
            'appearance_primary' => '#663344',
            'appearance_accent' => '#bb9966',
            'appearance_background' => '#f6f0e8',
            'google_client_secret' => 'super-secret-value',
        ];

        $this->actingAs($admin)->patch(route('admin.settings.update'), $payload)->assertRedirect();
        $storedSecret = SiteSetting::query()->where('key', 'google_client_secret')->firstOrFail();
        $this->assertNotSame('super-secret-value', $storedSecret->value);
        $this->assertSame('super-secret-value', Crypt::decryptString($storedSecret->value));

        $this->get('/')->assertSee('--wine:#663344', false)->assertSee('CLARE TEST');
    }

    public function test_wishlist_and_recent_views_are_visible_in_the_customer_account(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $product = Product::query()->where('slug', 'ru-dem')->firstOrFail();

        $this->actingAs($customer)->get(route('catalog.products.show', $product))->assertOk();
        $this->actingAs($customer)->post(route('wishlist.toggle', $product))->assertRedirect();

        $this->actingAs($customer)->get(route('account.show'))
            ->assertOk()
            ->assertSee('Sản phẩm yêu thích')
            ->assertSee('Đã xem gần đây')
            ->assertSee('Ru Đêm');
    }

    private function completedOrder(User $customer): Order
    {
        return Order::query()->create([
            'number' => 'CLR-REVIEW-001',
            'user_id' => $customer->getKey(),
            'status' => 'completed',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'currency' => 'VND',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0901234567',
            'shipping_recipient_name' => $customer->name,
            'shipping_phone' => '0901234567',
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'shipping_country_code' => 'VN',
            'subtotal' => 2490000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 2490000,
            'placed_at' => now()->subDays(4),
            'delivered_at' => now()->subDay(),
        ]);
    }
}
