<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\ExpirePendingPayPalPaymentsAction;
use App\Modules\Orders\Actions\SendOrderConfirmationAction;
use App\Modules\Orders\Mail\OrderPlacedMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        Cache::flush();
        config()->set('services.paypal.client_id', 'sandbox-client-id');
        config()->set('services.paypal.client_secret', 'sandbox-client-secret');
        config()->set('services.paypal.webhook_id', 'sandbox-webhook-id');
        config()->set('services.paypal.currency', 'USD');
        config()->set('services.paypal.vnd_per_unit', 25000);
        config()->set('mail.default', 'smtp');
    }

    public function test_paypal_checkout_creates_gateway_order_and_redirects_to_approval_url(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $cart = $this->cartWithOneItem();

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
                'expires_in' => 32400,
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-001',
                'status' => 'CREATED',
                'links' => [[
                    'rel' => 'payer-action',
                    'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-001',
                ]],
            ], 201),
        ]);

        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'paypal',
            ]);

        $response->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-001');

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('paypal', $payment->provider);
        $this->assertSame('PAYPAL-ORDER-001', $payment->provider_reference);
        $this->assertSame('USD', $payment->gateway_currency);
        $this->assertNotNull($payment->gateway_amount);
        $this->assertNotNull($payment->expires_at);
        Mail::assertNothingSent();
    }

    public function test_paypal_return_captures_payment_and_sends_confirmation_once(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $order = $this->paypalOrder($customer);
        $payment = $order->payments()->create([
            'provider' => 'paypal',
            'provider_reference' => 'PAYPAL-ORDER-002',
            'amount' => $order->total,
            'currency' => 'VND',
            'gateway_amount' => 70.00,
            'gateway_currency' => 'USD',
            'exchange_rate' => 25000,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-access-token']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-002/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'id' => 'PAYPAL-CAPTURE-002',
                        'status' => 'COMPLETED',
                        'amount' => ['value' => '70.00', 'currency_code' => 'USD'],
                    ]]],
                ]],
            ]),
        ]);

        $response = $this->actingAs($customer)
            ->get(route('payments.paypal.return', ['token' => 'PAYPAL-ORDER-002']));

        $response->assertRedirectContains('/checkout/orders/'.$order->number.'/complete');
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('data-payment-success-modal', false)
            ->assertSee('data-payment-success-force="true"', false)
            ->assertSee('Thanh toán thành công');
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('PAYPAL-CAPTURE-002', $payment->fresh()->provider_transaction_id);
        Mail::assertSent(OrderPlacedMail::class, 1);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertDontSee('data-payment-success-modal', false);

        $this->actingAs($customer)
            ->get(route('account.orders.show', ['order' => $order, 'payment' => 'success']))
            ->assertOk()
            ->assertSee('data-payment-success-modal', false);

        $this->actingAs($customer)
            ->get(route('payments.paypal.return', ['token' => 'PAYPAL-ORDER-002']))
            ->assertRedirectContains('/checkout/orders/'.$order->number.'/complete');
        Mail::assertSent(OrderPlacedMail::class, 1);
    }

    public function test_verified_completed_webhook_is_idempotent(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $order = $this->paypalOrder($customer);
        $payment = $order->payments()->create([
            'provider' => 'paypal',
            'provider_reference' => 'PAYPAL-ORDER-003',
            'amount' => $order->total,
            'currency' => 'VND',
            'gateway_amount' => 70.00,
            'gateway_currency' => 'USD',
            'exchange_rate' => 25000,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);
        $event = [
            'id' => 'WH-EVENT-003',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'PAYPAL-CAPTURE-003',
                'amount' => ['value' => '70.00', 'currency_code' => 'USD'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-003']],
            ],
        ];

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-access-token']),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ]),
        ]);

        $headers = [
            'PayPal-Auth-Algo' => 'SHA256withRSA',
            'PayPal-Cert-Url' => 'https://api-m.sandbox.paypal.com/cert.pem',
            'PayPal-Transmission-Id' => 'transmission-003',
            'PayPal-Transmission-Sig' => 'signature',
            'PayPal-Transmission-Time' => now()->toIso8601String(),
        ];

        $this->withHeaders($headers)->postJson(route('webhooks.paypal'), $event)->assertOk();
        $this->withHeaders($headers)->postJson(route('webhooks.paypal'), $event)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('payment_status_histories', 1);
        Mail::assertSent(OrderPlacedMail::class, 1);
    }

    public function test_expired_paypal_order_is_cancelled_and_inventory_is_restored(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $cart = $this->cartWithOneItem();
        $variantId = $cart->items()->value('product_variant_id');
        $stockBefore = ProductVariant::query()->findOrFail($variantId)->stock_quantity;

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-access-token']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-EXPIRED',
                'status' => 'CREATED',
                'links' => [[
                    'rel' => 'payer-action',
                    'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-EXPIRED',
                ]],
            ], 201),
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'customer_name' => 'Nguyễn Minh An',
                'customer_phone' => '0901234567',
                'payment_method' => 'paypal',
            ])
            ->assertRedirect();

        $payment = Payment::query()->firstOrFail();
        $this->travel(31)->minutes();

        $this->assertSame(1, app(ExpirePendingPayPalPaymentsAction::class)->execute());
        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('cancelled', $payment->order->fresh()->status);
        $this->assertSame($stockBefore, ProductVariant::query()->findOrFail($variantId)->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $payment->order_id,
            'product_variant_id' => $variantId,
            'type' => 'order_cancelled',
            'quantity' => 1,
        ]);
    }

    public function test_log_mailer_is_not_recorded_as_delivered_and_smtp_can_send_once(): void
    {
        $order = $this->paypalOrder($this->customer());
        $sendConfirmation = app(SendOrderConfirmationAction::class);

        config()->set('mail.default', 'log');
        $this->assertFalse($sendConfirmation->execute($order));
        $this->assertNull($order->fresh()->confirmation_email_sent_at);

        Mail::fake();
        config()->set('mail.default', 'smtp');
        $this->assertTrue($sendConfirmation->execute($order));
        $this->assertNotNull($order->fresh()->confirmation_email_sent_at);
        Mail::assertSent(OrderPlacedMail::class, 1);

        $this->assertTrue($sendConfirmation->execute($order));
        Mail::assertSent(OrderPlacedMail::class, 1);

        $this->assertTrue($sendConfirmation->execute($order, force: true));
        Mail::assertSent(OrderPlacedMail::class, 2);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Minh An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
        ]);
    }

    private function cartWithOneItem(): Cart
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $cart->items()->create(['product_variant_id' => $variant->getKey(), 'quantity' => 1]);

        return $cart;
    }

    private function paypalOrder(User $customer): Order
    {
        return Order::query()->create([
            'number' => 'CLR-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $customer->getKey(),
            'status' => 'pending',
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
            'currency' => 'VND',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipping_recipient_name' => $customer->name,
            'shipping_phone' => $customer->phone,
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Phường Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'shipping_country_code' => 'VN',
            'subtotal' => 1700000,
            'shipping_fee' => 50000,
            'discount_total' => 0,
            'total' => 1750000,
            'placed_at' => now(),
        ]);
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
