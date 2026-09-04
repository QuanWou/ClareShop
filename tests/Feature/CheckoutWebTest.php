<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\ConfirmMomoPaymentAction;
use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            ->assertSee('Giao Hàng Nhanh (GHN)')
            ->assertSee('Giao Hàng Tiết Kiệm (GHTK)')
            ->assertSee('J&amp;T Express', false)
            ->assertSee('QR ngân hàng qua payOS')
            ->assertSee('Ví MoMo')
            ->assertSee('PayPal')
            ->assertSee('Mua trước, trả sau')
            ->assertSee('Ngày nhận dự kiến')
            ->assertSee('Cách tính')
            ->assertDontSee('HỌ VÀ TÊN ĐẶT HÀNG')
            ->assertDontSee('EMAIL NHẬN THÔNG BÁO')
            ->assertDontSee('SỐ ĐIỆN THOẠI LIÊN HỆ')
            ->assertSee('data-address-summary-toggle', false)
            ->assertSee('data-address-picker-confirm', false)
            ->assertSee('data-custom-address-panel', false)
            ->assertSee('data-checkout-form', false)
            ->assertSee('data-checkout-discount-feedback', false);
    }

    public function test_saved_address_is_selected_and_can_be_used_without_retyping_shipping_fields(): void
    {
        $customer = $this->customer();
        $address = $customer->addresses()->create([
            'label' => 'Nhà riêng',
            'recipient_name' => 'Nguyễn Minh An',
            'phone' => '0901234567',
            'address_line_1' => '12 Nguyễn Huệ',
            'address_line_2' => 'Tầng 8',
            'ward' => 'Phường Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'postal_code' => '700000',
            'country_code' => 'VN',
            'is_default' => false,
        ]);
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('is-address-collapsed', false)
            ->assertSee('data-address-line-1="12 Nguyễn Huệ"', false)
            ->assertSee('value="'.$address->getKey().'"', false)
            ->assertSee('data-has-initial-quote="true"', false)
            ->assertDontSee('data-shipping-option-price="ghn">Nhập địa chỉ', false)
            ->assertDontSee('data-shipping-option-price="ghtk">Nhập địa chỉ', false)
            ->assertDontSee('data-shipping-option-price="jnt">Nhập địa chỉ', false);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                'saved_address' => $address->getKey(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'cod',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_city' => 'Hồ Chí Minh',
        ]);
    }

    public function test_checkout_creates_an_order_from_selected_items_and_keeps_the_rest_in_cart(): void
    {
        $customer = $this->customer();
        $selectedVariant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $savedForLaterVariant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = Cart::query()->create([
            'user_id' => $customer->getKey(),
            'currency' => 'VND',
        ]);
        $selectedItem = $cart->items()->create([
            'product_variant_id' => $selectedVariant->getKey(),
            'quantity' => 1,
            'is_selected' => true,
        ]);
        $savedForLaterItem = $cart->items()->create([
            'product_variant_id' => $savedForLaterVariant->getKey(),
            'quantity' => 1,
            'is_selected' => false,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'payment_method' => 'cod',
            ])
            ->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->getKey(),
            'product_variant_id' => $selectedVariant->getKey(),
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->getKey(),
            'product_variant_id' => $savedForLaterVariant->getKey(),
        ]);
        $this->assertDatabaseMissing('cart_items', ['id' => $selectedItem->getKey()]);
        $this->assertDatabaseHas('cart_items', [
            'id' => $savedForLaterItem->getKey(),
            'cart_id' => $cart->getKey(),
            'is_selected' => false,
        ]);
    }

    public function test_web_checkout_creates_a_bank_transfer_order_and_redirects_to_signed_confirmation(): void
    {
        $this->fakePayOsGateway();
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $secondVariant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);
        $cart->items()->create([
            'product_variant_id' => $secondVariant->getKey(),
            'quantity' => 2,
        ]);

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
            ->assertSee('payOS')
            ->assertSee('data-payos-qr', false)
            ->assertSee('PAYOS-QR-CONTENT');

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('id="payment-qr"', false)
            ->assertSee('Quét mã để thanh toán')
            ->assertSee('data-payos-qr', false)
            ->assertSee($order->number);

        $this->actingAs($customer)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('account-order-card', false)
            ->assertSee($variant->product->name)
            ->assertSee($secondVariant->product->name)
            ->assertSee($variant->sku)
            ->assertSee($secondVariant->sku)
            ->assertSee('×2')
            ->assertSee('QR ngân hàng qua payOS')
            ->assertSee('Thành tiền')
            ->assertSee('Xem lại mã QR')
            ->assertSee('account-order-item-image', false);

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

    public function test_web_checkout_records_momo_as_pending_without_claiming_it_was_paid(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'shipping_option' => 'ghtk',
                'customer_name' => 'Lê Mai',
                'customer_phone' => '0901234568',
                'payment_method' => 'momo',
            ]);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Đơn đang chờ thanh toán MoMo.')
            ->assertSee('Chỉ trạng thái do MoMo xác nhận mới được Clare ghi nhận là đã thanh toán.');

        $this->assertDatabaseHas('orders', [
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'shipping_provider' => 'Giao Hàng Tiết Kiệm (GHTK)',
        ]);
    }

    public function test_customer_can_create_a_new_momo_session_after_the_previous_session_expires(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Lê Mai',
                'customer_phone' => '0901234568',
                'payment_method' => 'momo',
            ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $payment->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        $order->update(['payment_status' => 'expired']);

        config()->set('services.momo.enabled', true);
        config()->set('services.momo.partner_code', 'MOMO');
        config()->set('services.momo.access_key', 'sandbox-access');
        config()->set('services.momo.secret_key', 'sandbox-secret');
        config()->set('services.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 0,
                'orderId' => $order->number,
                'payUrl' => 'https://test-payment.momo.vn/pay/clare-session',
            ]),
        ]);

        $this->actingAs($customer)
            ->post(route('payments.momo.retry', [$order, $payment]))
            ->assertRedirect('https://test-payment.momo.vn/pay/clare-session');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertTrue($payment->fresh()->expires_at->isFuture());
    }

    public function test_customer_can_change_an_expired_momo_order_to_cod(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Lê Mai',
                'customer_phone' => '0901234568',
                'payment_method' => 'momo',
            ]);

        $order = Order::query()->firstOrFail();
        $oldPayment = Payment::query()->firstOrFail();
        $oldPayment->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        $order->update(['payment_status' => 'expired']);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('Chọn cách tiếp tục')
            ->assertSee('Xác nhận phương thức mới')
            ->assertSee('Xác nhận hủy đơn');

        $this->actingAs($customer)
            ->patch(route('account.orders.payment-method.update', $order), [
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('account.orders.show', $order).'#payment-options')
            ->assertSessionHasNoErrors();

        $order->refresh();
        $newPayment = $order->payments()->latest('id')->firstOrFail();

        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('cod', $newPayment->provider);
        $this->assertSame('unpaid', $newPayment->status);
        $this->assertSame('expired', $oldPayment->fresh()->status);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payment_status_histories', [
            'payment_id' => $newPayment->getKey(),
            'from_status' => null,
            'to_status' => 'unpaid',
        ]);
    }

    public function test_cancelling_at_payos_immediately_unlocks_payment_choices_for_the_order(): void
    {
        $this->fakePayOsGateway();
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'bank_transfer',
            ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response = $this->actingAs($customer)->get(route('payments.payos.cancel', [
            'orderCode' => $payment->provider_reference,
        ]));

        $response->assertRedirectContains('/checkout/orders/'.$order->number.'/complete');
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Chọn cách tiếp tục')
            ->assertSee('Bạn đã hủy thanh toán payOS');
    }

    public function test_customer_can_cancel_an_unpaid_order_and_inventory_is_restored_only_once(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $stockBeforeCheckout = $variant->stock_quantity;
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
        $this->assertSame($stockBeforeCheckout - 1, $variant->fresh()->stock_quantity);

        $this->actingAs($customer)
            ->post(route('account.orders.cancel', $order), [
                'cancel_reason' => 'Tôi gặp vấn đề khi thanh toán',
                'cancel_note' => 'Tôi sẽ đặt lại sau.',
                'confirm_cancel' => '1',
            ])
            ->assertRedirect(route('account.orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('Tôi gặp vấn đề khi thanh toán: Tôi sẽ đặt lại sau.', $order->fresh()->cancel_reason);
        $this->assertSame($stockBeforeCheckout, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->getKey(),
            'product_variant_id' => $variant->getKey(),
            'type' => 'order_cancelled',
            'quantity' => 1,
        ]);

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.cancel', $order), [
                'cancel_reason' => 'Tôi không còn nhu cầu mua hàng',
                'confirm_cancel' => '1',
            ])
            ->assertRedirect(route('account.orders.show', $order))
            ->assertSessionHasErrors('cancel_reason');

        $this->assertSame($stockBeforeCheckout, $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_an_old_payment_cannot_be_confirmed_after_the_customer_changes_method(): void
    {
        $customer = $this->customer();
        $variant = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();
        $cart = $this->createGuestCartWithItem($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'momo',
            ]);

        $order = Order::query()->firstOrFail();
        $oldPayment = Payment::query()->firstOrFail();
        $oldPayment->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        $order->update(['payment_status' => 'expired']);

        $this->actingAs($customer)
            ->patch(route('account.orders.payment-method.update', $order), ['payment_method' => 'cod'])
            ->assertSessionHasNoErrors();

        try {
            app(ConfirmMomoPaymentAction::class)->execute(
                payment: $oldPayment,
                transactionId: 'LATE-MOMO-TRANSACTION',
                amount: (int) $oldPayment->amount,
            );

            $this->fail('Phiên MoMo cũ không được phép xác nhận sau khi đổi phương thức.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Phiên thanh toán này không còn hiệu lực đối với đơn hàng.',
                $exception->errors()['payment'][0],
            );
        }

        $this->assertSame('cod', $order->fresh()->payment_method);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertSame('expired', $oldPayment->fresh()->status);
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
            'paymentLinkId' => 'PAYOS-LINK-WEB',
            'status' => 'PENDING',
            'checkoutUrl' => 'https://pay.payos.vn/web/PAYOS-LINK-WEB',
            'qrCode' => 'PAYOS-QR-CONTENT',
        ]);
        $this->app->instance(PayOsClient::class, $client);
    }
}
