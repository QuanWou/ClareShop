<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Appointments\Actions\CreateAppointmentAction;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Promotions\Models\PromotionCode;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_routes_require_an_administrator_role(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_dashboard_and_operational_screens(): void
    {
        $admin = $this->admin();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $order = $this->createOrder($variant, 'cod');
        $appointment = app(CreateAppointmentAction::class)->execute(null, [
            'type' => 'consultation',
            'customer_name' => 'Lê Mai',
            'customer_email' => 'mai@example.test',
            'customer_phone' => '0901234568',
            'preferred_starts_at' => now()->addDays(2)->setTime(9, 0),
            'country_code' => 'VN',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tổng quan hôm nay.')
            ->assertSee('Phân bổ trạng thái')
            ->assertSee('Tồn kho thấp');

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->number);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Cập nhật trạng thái')
            ->assertSee('Thanh toán');

        $this->actingAs($admin)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee($appointment->number);

        $this->actingAs($admin)
            ->get(route('admin.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Cập nhật yêu cầu');
    }

    public function test_admin_can_cancel_an_eligible_order_and_inventory_is_restored_once(): void
    {
        $admin = $this->admin();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $stockBeforeCheckout = $variant->stock_quantity;
        $order = $this->createOrder($variant, 'cod', 2);

        $this->assertSame($stockBeforeCheckout - 2, $variant->fresh()->stock_quantity);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status.update', $order), [
                'status' => 'cancelled',
                'cancel_reason' => 'Khách đổi kế hoạch mua sắm.',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => 'cancelled',
            'cancel_reason' => 'Khách đổi kế hoạch mua sắm.',
        ]);
        $this->assertSame($stockBeforeCheckout, $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->getKey(),
            'type' => 'order_cancelled',
            'quantity' => 2,
            'balance_after' => $stockBeforeCheckout,
            'actor_id' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->getKey(),
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'changed_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status.update', $order), [
                'status' => 'cancelled',
                'cancel_reason' => 'Thử hủy lại.',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame($stockBeforeCheckout, $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_paid_order_must_be_recorded_as_refunded_before_cancellation(): void
    {
        $admin = $this->admin();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $stockBeforeCheckout = $variant->stock_quantity;
        $order = $this->createOrder($variant, 'bank_transfer');
        $payment = Payment::query()->where('order_id', $order->getKey())->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.payment-status.update', [$order, $payment]), [
                'payment_status' => 'paid',
                'payment_note' => 'Đã đối soát giao dịch ngân hàng VCB-001.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payment_status_histories', [
            'payment_id' => $payment->getKey(),
            'from_status' => 'pending',
            'to_status' => 'paid',
            'changed_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status.update', $order), [
                'status' => 'cancelled',
                'cancel_reason' => 'Khách yêu cầu hủy.',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->patch(route('admin.orders.payment-status.update', [$order, $payment]), [
                'payment_status' => 'refunded',
                'payment_note' => 'Đã hoàn tiền qua ngân hàng với mã RF-001.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->actingAs($admin)
            ->patch(route('admin.orders.status.update', $order), [
                'status' => 'cancelled',
                'cancel_reason' => 'Khách yêu cầu hủy sau hoàn tiền.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);
        $this->assertSame($stockBeforeCheckout, $variant->fresh()->stock_quantity);
    }

    public function test_admin_records_each_fulfillment_milestone_and_generates_a_tracking_number(): void
    {
        $admin = $this->admin();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $order = $this->createOrder($variant, 'cod');

        foreach (['confirmed', 'processing', 'shipped', 'completed'] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.orders.status.update', $order), [
                    'status' => $status,
                    'admin_note' => 'Cập nhật vận hành cho bài tập lớn.',
                ])
                ->assertRedirect(route('admin.orders.show', $order));

            $order->refresh();
        }

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->preparing_at);
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->shipping_tracking_number);
        $this->assertMatchesRegularExpression('/^CLR-SHP-/', $order->shipping_tracking_number);
        $this->assertDatabaseCount('order_status_histories', 5);
    }

    public function test_admin_can_create_and_disable_a_promotion_code_without_losing_its_audit_fields(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.promotions.store'), [
                'code' => 'summer-15',
                'name' => 'Ưu đãi mùa hè',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'minimum_order_amount' => 500000,
                'maximum_discount_amount' => 250000,
                'usage_limit' => 50,
                'is_active' => true,
            ])
            ->assertRedirect();

        $promotion = PromotionCode::query()->where('code', 'SUMMER-15')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.promotions.index'))
            ->assertOk()
            ->assertSeeText('SUMMER-15')
            ->assertSeeText('Ưu đãi mùa hè')
            ->assertSeeText('Ngay')
            ->assertSeeText('Không giới hạn');

        $this->assertSame(0, $promotion->usage_count);

        $this->actingAs($admin)
            ->patch(route('admin.promotions.update', $promotion), [
                'code' => 'SUMMER-15',
                'name' => 'Ưu đãi mùa hè đã đóng',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'minimum_order_amount' => 500000,
                'maximum_discount_amount' => 250000,
                'usage_limit' => 50,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.promotions.edit', $promotion));

        $this->assertDatabaseHas('promotion_codes', [
            'id' => $promotion->getKey(),
            'is_active' => false,
            'usage_count' => 0,
        ]);
    }

    public function test_admin_can_manage_catalog_products_variants_and_uploaded_images(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.catalog.categories.store'), [
                'name' => 'Đèn thử nghiệm',
                'description' => 'Danh mục dành cho kiểm thử back office.',
                'is_active' => true,
                'sort_order' => 12,
            ])
            ->assertRedirect();

        $category = Category::query()->where('name', 'Đèn thử nghiệm')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.catalog.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Ánh Chớm',
                'short_description' => 'Mẫu đèn thử nghiệm.',
                'is_active' => true,
                'is_featured' => false,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'initial_variant' => [
                    'sku' => 'ADMIN-TEST-PEARL',
                    'color_name' => 'Ngọc trai',
                    'color_hex' => '#F4EDE0',
                    'price' => 890000,
                    'stock_quantity' => 8,
                    'weight_grams' => 800,
                    'is_active' => true,
                    'sort_order' => 0,
                ],
            ])
            ->assertRedirect();

        $product = Product::query()->where('name', 'Ánh Chớm')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.catalog.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('anh-chom.jpg', 800, 800)->size(4096),
                'alt_text' => 'Đèn Ánh Chớm màu ngọc trai',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.catalog.products.edit', $product));

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->getKey(),
            'disk' => 'public',
            'alt_text' => 'Đèn Ánh Chớm màu ngọc trai',
        ]);
        $imagePath = $product->images()->value('path');
        Storage::disk('public')->assertExists($imagePath);

        $this->actingAs($admin)
            ->patch(route('admin.catalog.products.variants.update', [$product, $variant]), [
                'sku' => 'ADMIN-TEST-PEARL',
                'color_name' => 'Ngọc trai',
                'color_hex' => '#F4EDE0',
                'price' => 920000,
                'compare_at_price' => 990000,
                'stock_quantity' => 6,
                'weight_grams' => 850,
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.catalog.products.edit', $product));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->getKey(),
            'price' => 920000,
            'stock_quantity' => 6,
            'weight_grams' => 850,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.catalog.products.destroy', $product))
            ->assertRedirect(route('admin.catalog.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->getKey()]);
    }

    public function test_product_image_upload_reports_a_clear_error_above_five_megabytes(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = Product::query()->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.catalog.products.edit', $product))
            ->post(route('admin.catalog.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('anh-qua-lon.jpg')->size(5121),
                'sort_order' => 99,
            ])
            ->assertRedirect(route('admin.catalog.products.edit', $product))
            ->assertSessionHasErrors([
                'image' => 'Ảnh không được lớn hơn 5 MB.',
            ]);

        $this->assertDatabaseMissing('product_images', [
            'product_id' => $product->getKey(),
            'sort_order' => 99,
        ]);
    }

    public function test_admin_can_upload_an_existing_catalog_png(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = Product::query()->where('slug', 'goc-mo-som')->firstOrFail();
        $sourcePath = public_path('images/catalog/den-ban-thao-moc-ngu.png');

        $this->actingAs($admin)
            ->post(route('admin.catalog.products.images.store', $product), [
                'image' => new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true),
                'alt_text' => 'Ảnh PNG có sẵn trong catalog',
                'sort_order' => 99,
            ])
            ->assertRedirect(route('admin.catalog.products.edit', $product))
            ->assertSessionHasNoErrors();

        $imagePath = $product->images()->where('sort_order', 99)->value('path');

        $this->assertNotNull($imagePath);
        Storage::disk('public')->assertExists($imagePath);
    }

    public function test_admin_can_restore_archived_products_and_variants(): void
    {
        $admin = $this->admin();
        $product = Product::query()->where('slug', 'goc-mo-som')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.catalog.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.catalog.products.edit', $product));

        $this->assertSoftDeleted('product_variants', ['id' => $variant->getKey()]);

        $this->actingAs($admin)
            ->get(route('admin.catalog.products.edit', $product))
            ->assertOk()
            ->assertSeeText('Biến thể đã lưu trữ')
            ->assertSeeText($variant->sku)
            ->assertSeeText('Khôi phục biến thể');

        $archivedVariant = ProductVariant::withTrashed()->findOrFail($variant->getKey());

        $this->actingAs($admin)
            ->patch(route('admin.catalog.products.variants.restore', [$product, $archivedVariant]))
            ->assertRedirect(route('admin.catalog.products.edit', $product))
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('product_variants', ['id' => $variant->getKey()]);

        $this->actingAs($admin)
            ->delete(route('admin.catalog.products.destroy', $product))
            ->assertRedirect(route('admin.catalog.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->getKey()]);

        $this->actingAs($admin)
            ->get(route('admin.catalog.products.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSeeText($product->name)
            ->assertSeeText('Đã lưu trữ')
            ->assertSeeText('Khôi phục');

        $archivedProduct = Product::withTrashed()->findOrFail($product->getKey());

        $this->actingAs($admin)
            ->patch(route('admin.catalog.products.restore', $archivedProduct))
            ->assertRedirect(route('admin.catalog.products.edit', $product))
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('products', ['id' => $product->getKey()]);
    }

    public function test_admin_cannot_remove_their_own_or_the_last_admin_access(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->patch(route('admin.users.update', $admin), [
                'role' => 'customer',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors('role');

        $secondAdmin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $secondAdmin), [
                'role' => 'customer',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.users.edit', $secondAdmin));

        $this->assertDatabaseHas('users', [
            'id' => $secondAdmin->getKey(),
            'role' => 'customer',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_confirm_a_service_request_with_a_manual_schedule(): void
    {
        $admin = $this->admin();
        $appointment = app(CreateAppointmentAction::class)->execute(null, [
            'type' => 'installation',
            'customer_name' => 'Lê Mai',
            'customer_email' => 'mai@example.test',
            'customer_phone' => '0901234568',
            'preferred_starts_at' => now()->addDays(2)->setTime(9, 0),
            'address_line_1' => '18 Nguyễn Huệ',
            'ward' => 'Phường Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'country_code' => 'VN',
        ]);
        $scheduledStart = now()->addDays(3)->setTime(10, 0);

        $this->actingAs($admin)
            ->patch(route('admin.appointments.status.update', $appointment), [
                'status' => 'confirmed',
                'scheduled_starts_at' => $scheduledStart->format('Y-m-d H:i:s'),
                'scheduled_ends_at' => $scheduledStart->copy()->addHour()->format('Y-m-d H:i:s'),
                'internal_note' => 'Nhân viên sẽ liên hệ trước khi đến.',
            ])
            ->assertRedirect(route('admin.appointments.show', $appointment))
            ->assertSessionHasNoErrors();

        $appointment->refresh();

        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame($admin->getKey(), $appointment->confirmed_by);
        $this->assertTrue($appointment->scheduled_starts_at->equalTo($scheduledStart));
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $appointment->getKey(),
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'changed_by' => $admin->getKey(),
        ]);
    }

    public function test_admin_can_create_update_and_safely_delete_a_customer_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Trần Bảo Ngọc',
                'email' => 'bao.ngoc@example.test',
                'phone' => '0901234567',
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
                'role' => 'customer',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'bao.ngoc@example.test')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Trần Bảo Ngọc Mới',
                'email' => 'bao.ngoc.moi@example.test',
                'phone' => '0912345678',
                'password' => 'Matkhau456',
                'password_confirmation' => 'Matkhau456',
                'role' => 'customer',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => 'Trần Bảo Ngọc Mới',
            'email' => 'bao.ngoc.moi@example.test',
            'phone' => '0912345678',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user), ['delete_confirmation' => true])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $user->getKey()]);
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => 'Tài khoản đã xóa',
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_delete_the_account_used_for_the_current_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->delete(route('admin.users.destroy', $admin), ['delete_confirmation' => true])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors('account_deletion');

        $this->assertNotSoftDeleted('users', ['id' => $admin->getKey()]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createOrder(ProductVariant $variant, string $paymentMethod, int $quantity = 1): Order
    {
        $customer = User::factory()->create([
            'name' => 'Nguyễn Minh An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
        ]);
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => $quantity,
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => $paymentMethod,
            ])
            ->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function shippingAddress(): array
    {
        return [
            'shipping_recipient_name' => 'Nguyễn Minh An',
            'shipping_phone' => '0901234567',
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Phường Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'shipping_country_code' => 'VN',
        ];
    }
}
