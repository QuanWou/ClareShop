<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_guest_is_redirected_to_login_before_checkout(): void
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_page_shows_cart_summary_and_shipping_form_for_the_signed_in_customer(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Hoàn tất đơn hàng')
            ->assertSee('Ru Đêm')
            ->assertSee('Chuyển khoản qua VietQR')
            ->assertSee('Ngày nhận dự kiến')
            ->assertSee('Cách tính')
            ->assertSee($customer->email)
            ->assertSee('data-checkout-form', false)
            ->assertSee('data-checkout-discount-feedback', false);
    }

    public function test_web_checkout_creates_a_bank_transfer_order_and_redirects_to_signed_confirmation(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'bank_transfer',
            ]);

        $order = Order::query()->firstOrFail();

        $response
            ->assertRedirectContains('/checkout/orders/'.$order->number.'/complete')
            ->assertSessionHasNoErrors();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Quét mã để thanh toán')
            ->assertSee($order->number)
            ->assertSee('VietQR')
            ->assertSee('addInfo='.rawurlencode($order->number), false);

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($customer->getKey(), $order->user_id);
        $this->assertDatabaseMissing('carts', ['id' => $cart->getKey()]);
    }

    public function test_web_checkout_confirms_cod_payment_at_delivery(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Lê Mai',
                'customer_phone' => '0901234568',
                'payment_method' => 'cod',
            ]);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Thanh toán khi đơn được giao.')
            ->assertSee('Tổng thanh toán');

        $this->assertDatabaseHas('orders', [
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_another_customer_cannot_open_a_signed_confirmation_link(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
            ]);

        $this->actingAs(User::factory()->create())
            ->get($response->headers->get('Location'))
            ->assertNotFound();
    }

    public function test_customer_can_follow_their_own_order_from_the_account_but_not_another_customers_order(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
            ]);

        $order = Order::query()->firstOrFail();

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('Theo dõi đơn hàng')
            ->assertSee('Chờ xác nhận');

        $this->actingAs(User::factory()->create())
            ->get(route('account.orders.show', $order))
            ->assertNotFound();
    }

    private function createGuestCartWithItem(ProductVariant $variant): Cart
    {
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);

        $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
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
}
