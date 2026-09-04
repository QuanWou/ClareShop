<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Mail\OrderPlacedMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayOsCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'smtp');
    }

    public function test_verified_payos_webhook_confirms_payment_once(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);
        $payload = ['signature' => 'verified-by-client', 'data' => ['orderCode' => 260829001]];
        $verifiedData = [
            'orderCode' => 260829001,
            'amount' => 1750000,
            'description' => 'CLARE '.$order->getKey(),
            'reference' => 'PAYOS-TRANSACTION-001',
            'paymentLinkId' => 'PAYOS-LINK-001',
            'code' => '00',
            'desc' => 'success',
        ];
        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('verifyWebhook')->twice()->andReturn($verifiedData);
        $this->app->instance(PayOsClient::class, $client);

        $this->postJson(route('webhooks.payos'), $payload)->assertOk()->assertJsonPath('status', 'processed');
        $this->postJson(route('webhooks.payos'), $payload)->assertOk()->assertJsonPath('status', 'processed');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('PAYOS-TRANSACTION-001', $payment->fresh()->provider_transaction_id);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('payment_status_histories', 1);
        Mail::assertSent(OrderPlacedMail::class, 1);
    }

    public function test_payos_return_verifies_status_with_api_before_showing_success(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);
        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('getPayment')->once()->with('260829001')->andReturn([
            'id' => 'PAYOS-LINK-001',
            'status' => 'PAID',
            'amount' => 1750000,
            'amountPaid' => 1750000,
            'transactions' => [['reference' => 'PAYOS-TRANSACTION-RETURN']],
        ]);
        $this->app->instance(PayOsClient::class, $client);

        $response = $this->actingAs($customer)->get(route('payments.payos.return', ['orderCode' => '260829001']));

        $response
            ->assertRedirectContains('/checkout/orders/'.$order->number.'/complete')
            ->assertSessionHas('payment_success', true);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('PAYOS-TRANSACTION-RETURN', $payment->fresh()->provider_transaction_id);
    }

    public function test_payment_status_poll_reconciles_a_late_payos_transfer_after_local_expiry(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);
        $payment->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        $order->update(['payment_status' => 'expired']);

        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('getPayment')->once()->with('260829001')->andReturn([
            'id' => 'PAYOS-LINK-LATE',
            'status' => 'PAID',
            'amount' => 1750000,
            'amountPaid' => 1750000,
            'transactions' => [['reference' => 'PAYOS-TRANSACTION-LATE']],
        ]);
        $this->app->instance(PayOsClient::class, $client);

        $this->actingAs($customer)
            ->getJson(route('account.orders.payment-status', $order))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('PAYOS-TRANSACTION-LATE', $payment->fresh()->provider_transaction_id);
    }

    public function test_scheduler_reconciles_a_pending_payos_payment_without_browser_or_webhook(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);

        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('getPayment')->once()->with('260829001')->andReturn([
            'id' => 'PAYOS-LINK-SCHEDULER',
            'status' => 'PAID',
            'amount' => 1750000,
            'amountPaid' => 1750000,
            'transactions' => [['reference' => 'PAYOS-TRANSACTION-SCHEDULER']],
        ]);
        $this->app->instance(PayOsClient::class, $client);

        $result = app(\App\Modules\Orders\Actions\ReconcilePendingPayOsPaymentsAction::class)->execute();

        $this->assertSame(['checked' => 1, 'confirmed' => 1, 'failed' => 0], $result);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('PAYOS-TRANSACTION-SCHEDULER', $payment->fresh()->provider_transaction_id);
    }

    public function test_paid_payos_order_does_not_offer_an_expired_qr_or_retry_action(): void
    {
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'expires_at' => now()->subMinute(),
            'provider_transaction_id' => 'PAYOS-TRANSACTION-PAID',
        ]);
        $order->update(['payment_status' => 'paid']);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('payOS · Đã thanh toán')
            ->assertDontSee('Phiên QR không còn hiệu lực.')
            ->assertDontSee('Tạo mã payOS mới');
    }

    public function test_customer_can_replace_an_expired_payos_qr_but_cannot_retry_another_customers_payment(): void
    {
        $customer = User::factory()->create();
        $order = $this->payOsOrder($customer);
        $payment = $this->payOsPayment($order);
        $payment->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        $order->update(['payment_status' => 'expired']);

        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('createPayment')->once()->andReturnUsing(fn (array $data): array => [
            'bin' => '970422',
            'accountNumber' => '113366668888',
            'accountName' => 'CLARE TEST',
            'amount' => $data['amount'],
            'description' => $data['description'],
            'orderCode' => $data['orderCode'],
            'currency' => 'VND',
            'paymentLinkId' => 'PAYOS-LINK-RETRY',
            'status' => 'PENDING',
            'checkoutUrl' => 'https://pay.payos.vn/web/PAYOS-LINK-RETRY',
            'qrCode' => 'PAYOS-QR-RETRY',
        ]);
        $this->app->instance(PayOsClient::class, $client);

        $this->actingAs(User::factory()->create())
            ->post(route('payments.payos.retry', [$order, $payment]))
            ->assertNotFound();

        $this->actingAs($customer)
            ->post(route('payments.payos.retry', [$order, $payment]))
            ->assertRedirectContains('/account/orders/'.$order->number.'#payment-qr');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('PAYOS-QR-RETRY', $payment->fresh()->payload['qr_code']);
        $this->assertTrue($payment->fresh()->expires_at->isFuture());
    }

    private function payOsOrder(User $customer): Order
    {
        return Order::query()->create([
            'number' => 'CLR-PAYOS-'.Str::upper(Str::random(6)),
            'user_id' => $customer->getKey(),
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'currency' => 'VND',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0901234567',
            'shipping_recipient_name' => $customer->name,
            'shipping_phone' => '0901234567',
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

    private function payOsPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'provider' => 'payos',
            'provider_reference' => '260829001',
            'amount' => $order->total,
            'currency' => 'VND',
            'status' => 'pending',
            'approval_url' => 'https://pay.payos.vn/web/PAYOS-LINK-001',
            'expires_at' => now()->addMinutes(3),
            'payload' => [
                'payment_link_id' => 'PAYOS-LINK-001',
                'qr_code' => 'PAYOS-QR-001',
                'checkout_url' => 'https://pay.payos.vn/web/PAYOS-LINK-001',
                'transfer_content' => 'CLARE '.$order->getKey(),
                'amount' => (int) $order->total,
            ],
        ]);
    }
}
