<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\TransitionOrderStatusAction;
use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Models\PromotionCode;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_checkout_quote_calculates_server_side_totals_from_address_and_weight(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.quote'), $this->shippingAddress());

        $shippingOption = config('checkout.shipping.providers.ghn');
        $expectedShippingFee = $shippingOption['base_fee']
            + (int) ceil(
                max(0, $variant->weight_grams - $shippingOption['included_weight_grams'])
                / $shippingOption['additional_weight_block_grams'],
            ) * $shippingOption['additional_weight_fee'];
        $expectedAdditionalBlocks = (int) ceil(
            max(0, $variant->weight_grams - $shippingOption['included_weight_grams'])
            / $shippingOption['additional_weight_block_grams'],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.subtotal', 1690000)
            ->assertJsonPath('data.shipping.option', 'ghn')
            ->assertJsonPath('data.shipping.provider', 'Giao Hàng Nhanh (GHN)')
            ->assertJsonPath('data.shipping.total_weight_grams', $variant->weight_grams)
            ->assertJsonPath('data.shipping.fee', $expectedShippingFee)
            ->assertJsonPath('data.shipping.calculation.base_fee', $shippingOption['base_fee'])
            ->assertJsonPath('data.shipping.calculation.additional_weight_blocks', $expectedAdditionalBlocks)
            ->assertJsonPath('data.shipping.calculation.is_urban_destination', true)
            ->assertJsonPath('data.shipping.is_estimated', true)
            ->assertJsonCount(3, 'data.shipping_options')
            ->assertJsonPath('data.total', 1690000 + $expectedShippingFee);
    }

    public function test_checkout_quote_uses_vietnam_time_and_business_days_for_the_delivery_estimate(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 14, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

        try {
            $customer = $this->customer();
            $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
            $variant->product()->update(['published_at' => now()->subDay()]);
            $cart = $this->createGuestCartWithItem($variant, 1);

            $this->actingAs($customer)
                ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
                ->withCredentials()
                ->postJson(route('checkout.quote'), $this->shippingAddress())
                ->assertOk()
                ->assertJsonPath('data.shipping.estimated_days', 2)
                ->assertJsonPath('data.shipping.estimated_days_label', '2 ngày làm việc')
                ->assertJsonPath('data.shipping.estimated_delivery_at', '2026-08-18T18:00:00+07:00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_bank_transfer_checkout_creates_snapshots_reduces_stock_and_returns_payos_qr(): void
    {
        $this->fakePayOsGateway();
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 2);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_email' => 'gia-mao@example.test',
                'customer_phone' => '0901234567',
                'payment_method' => 'bank_transfer',
                'customer_note' => 'Giao giờ hành chính.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.order.status', 'pending')
            ->assertJsonPath('data.order.payment_method', 'bank_transfer')
            ->assertJsonPath('data.order.payment_status', 'pending')
            ->assertJsonPath('data.payment.provider', 'payos')
            ->assertJsonPath('data.payment.status', 'pending')
            ->assertJsonPath('data.payment.payos.bank_id', '970422')
            ->assertJsonPath('data.payment.payos.account_number', '113366668888')
            ->assertJsonPath('data.payment.payos.amount', $response->json('data.order.total'))
            ->assertJsonPath('data.payment.payos.qr_code', 'PAYOS-QR-CONTENT')
            ->assertJsonPath('data.shipping.fee_is_estimated', true);

        $order = Order::query()->firstOrFail();

        $this->assertSame($customer->getKey(), $order->user_id);
        $this->assertSame($customer->email, $order->customer_email);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->getKey(),
            'product_variant_id' => $variant->getKey(),
            'product_name' => 'Ru Đêm',
            'color_name' => 'Đỏ vang',
            'sku' => 'CLR-RD-BURGUNDY',
            'quantity' => 2,
            'line_total' => 4980000,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'provider' => 'payos',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('payment_status_histories', [
            'payment_id' => $order->payments()->value('id'),
            'from_status' => null,
            'to_status' => 'pending',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->getKey(),
            'product_variant_id' => $variant->getKey(),
            'type' => 'order_placed',
            'quantity' => -2,
            'balance_after' => 6,
        ]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->getKey()]);
        $this->assertSame(6, $variant->fresh()->stock_quantity);
    }

    public function test_cod_checkout_stays_unpaid_without_qr_data(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Lê Mai',
                'customer_phone' => '0901234568',
                'payment_method' => 'cod',
            ])
            ->assertCreated()
            ->assertJsonPath('data.order.payment_status', 'unpaid')
            ->assertJsonPath('data.payment.provider', 'cod')
            ->assertJsonPath('data.payment.status', 'unpaid')
            ->assertJsonPath('data.payment.payos', null);
    }

    public function test_checkout_returns_and_snapshots_the_customer_selected_shipping_option(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.quote'), [
                ...$this->shippingAddress(),
                'shipping_option' => 'jnt',
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping.option', 'jnt')
            ->assertJsonPath('data.shipping.provider', 'J&T Express')
            ->assertJsonPath('data.shipping.service', 'Tiêu chuẩn')
            ->assertJsonCount(3, 'data.shipping_options');

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'shipping_option' => 'jnt',
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipping.provider', 'J&T Express');

        $this->assertDatabaseHas('orders', [
            'shipping_provider' => 'J&T Express',
            'shipping_service' => 'Tiêu chuẩn',
            'shipping_fee_is_estimated' => true,
        ]);
    }

    public function test_checkout_creates_pending_payment_records_for_momo_and_pay_later(): void
    {
        $methods = [
            'momo' => 'momo',
            'pay_later' => 'pay_later_review',
        ];

        foreach ($methods as $method => $provider) {
            $customer = User::factory()->create();
            $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
            $cart = $this->createGuestCartWithItem($variant, 1);

            $response = $this->actingAs($customer)
                ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
                ->withCredentials()
                ->postJson(route('checkout.orders.store'), [
                    ...$this->shippingAddress(),
                    'shipping_option' => 'ghtk',
                    'customer_name' => 'Khách thử nghiệm',
                    'customer_phone' => '0901234567',
                    'payment_method' => $method,
                ]);

            $response
                ->assertCreated()
                ->assertJsonPath('data.order.payment_method', $method)
                ->assertJsonPath('data.order.payment_status', 'pending')
                ->assertJsonPath('data.payment.provider', $provider)
                ->assertJsonPath('data.payment.status', 'pending')
                ->assertJsonPath('data.payment.integration_status', 'pending_gateway_integration')
                ->assertJsonPath('data.payment.payos', null);
        }

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_checkout_calculates_and_snapshots_an_eligible_promotion_code_on_the_server(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);
        $promotion = PromotionCode::query()->create([
            'code' => 'WELCOME10',
            'name' => 'Ưu đãi khách mới',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 1000000,
            'maximum_discount_amount' => 200000,
            'usage_limit' => 1,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.quote'), [
                ...$this->shippingAddress(),
                'discount_code' => 'welcome10',
            ])
            ->assertOk()
            ->assertJsonPath('data.discount.applied', true)
            ->assertJsonPath('data.discount.code', 'WELCOME10')
            ->assertJsonPath('data.discount.amount', 169000)
            ->assertJsonPath('data.discount_total', 169000);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
                'discount_code' => 'welcome10',
            ])
            ->assertCreated()
            ->assertJsonPath('data.order.discount_total', 169000);

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('order_discounts', [
            'order_id' => $order->getKey(),
            'promotion_code_id' => $promotion->getKey(),
            'code' => 'WELCOME10',
            'discount_amount' => 169000,
        ]);
        $this->assertSame(0, $promotion->fresh()->usage_count);
        $this->assertDatabaseHas('voucher_reservations', [
            'order_id' => $order->getKey(),
            'promotion_code_id' => $promotion->getKey(),
            'status' => 'reserved',
        ]);
        $this->assertNotNull($order->estimated_delivery_at);
    }

    public function test_checkout_rejects_an_expired_or_exhausted_promotion_code_without_creating_an_order(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);
        $ghn = config('checkout.shipping.providers.ghn');
        $expectedShippingFee = $ghn['base_fee'] + (int) ceil(
            max(0, $variant->weight_grams - $ghn['included_weight_grams']) / $ghn['additional_weight_block_grams'],
        ) * $ghn['additional_weight_fee'];
        PromotionCode::query()->create([
            'code' => 'HETHAN',
            'name' => 'Mã đã hết hạn',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'is_active' => true,
            'ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.quote'), [
                ...$this->shippingAddress(),
                'discount_code' => 'HETHAN',
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping.fee', $expectedShippingFee)
            ->assertJsonPath('data.discount.applied', false)
            ->assertJsonPath('data.discount.message', 'Mã ưu đãi hiện không còn hiệu lực.');

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
                'discount_code' => 'HETHAN',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('discount_code');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_cancelling_an_order_returns_the_promotion_usage_once(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);
        $promotion = PromotionCode::query()->create([
            'code' => 'HUYDON',
            'name' => 'Ưu đãi hủy đơn',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
                'discount_code' => 'HUYDON',
            ])
            ->assertCreated();

        $order = Order::query()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        app(TransitionOrderStatusAction::class)->execute(
            order: $order,
            actorId: (int) $admin->getKey(),
            nextStatus: 'cancelled',
            note: null,
            cancelReason: 'Khách đổi ý.',
        );

        $this->assertSame(0, $promotion->fresh()->usage_count);
        $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'status' => 'cancelled']);
    }

    public function test_checkout_revalidates_stock_before_creating_an_order(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $cart = Cart::query()->create([
            'user_id' => $customer->getKey(),
            'currency' => 'VND',
        ]);
        $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => $variant->stock_quantity + 1,
        ]);

        $this->actingAs($customer)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Thu',
                'customer_phone' => '0901234569',
                'payment_method' => 'cod',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(4, $variant->fresh()->stock_quantity);
    }

    public function test_guest_cannot_request_a_quote_or_create_an_order(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant, 1);

        $this->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->postJson(route('checkout.quote'), $this->shippingAddress())
            ->assertUnauthorized();

        $this->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->postJson(route('checkout.orders.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
            ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->getKey()]);
    }

    private function createGuestCartWithItem(ProductVariant $variant, int $quantity): Cart
    {
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);

        $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => $quantity,
        ]);

        return $cart;
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

    private function customer(): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Minh An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
        ]);
    }

    private function fakePayOsGateway(): void
    {
        config()->set('services.payos.enabled', true);
        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('createPayment')->once()->andReturnUsing(fn (array $data): array => [
            'bin' => '970422',
            'accountNumber' => '113366668888',
            'accountName' => 'CLARE TEST',
            'amount' => $data['amount'],
            'description' => $data['description'],
            'orderCode' => $data['orderCode'],
            'currency' => 'VND',
            'paymentLinkId' => 'PAYOS-LINK-TEST',
            'status' => 'PENDING',
            'checkoutUrl' => 'https://pay.payos.vn/web/PAYOS-LINK-TEST',
            'qrCode' => 'PAYOS-QR-CONTENT',
        ]);
        $this->app->instance(PayOsClient::class, $client);
    }
}
