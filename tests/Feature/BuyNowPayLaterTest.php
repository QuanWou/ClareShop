<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Billing\Actions\UpdatePayLaterStatusesAction;
use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\ClarePayTransaction;
use App\Modules\Billing\Models\ClarePayWallet;
use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Billing\Notifications\PayLaterNotice;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Order;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuyNowPayLaterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_checkout_keeps_the_required_name_and_paypal_only_in_the_description(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Mua trước, trả sau')
            ->assertSee('Thanh toán sau qua PayPal')
            ->assertSee('Thanh toán sau 2 tháng')
            ->assertSee('Thanh toán sau 4 tháng');
    }

    public function test_customer_can_place_two_and_four_month_pay_later_orders_with_correct_due_dates(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');

        foreach ([2 => '2026-11-15', 4 => '2027-01-15'] as $term => $dueDate) {
            $user = $this->userWithCart();
            $this->actingAs($user)->post(route('checkout.store'), [
                ...$this->shippingAddress(),
                'shipping_option' => 'ghn',
                'payment_method' => 'pay_later',
                'pay_later_term_months' => $term,
                'pay_later_confirm' => '1',
            ])->assertSessionHasNoErrors();

            $purchase = PayLaterPurchase::query()->where('user_id', $user->getKey())->firstOrFail();
            $this->assertSame($term, $purchase->term_months);
            $this->assertSame($dueDate, $purchase->due_at->format('Y-m-d'));
            $this->assertSame('active', $purchase->status);
            $this->assertSame('unpaid', $purchase->order->payment_status);
            $this->assertFalse((bool) data_get($purchase->order->payments()->latest()->first()->payload, 'automatic_debit'));
        }
    }

    public function test_term_and_confirmation_are_required_for_pay_later(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->from(route('checkout.show'))->post(route('checkout.store'), [
            ...$this->shippingAddress(),
            'shipping_option' => 'ghn',
            'payment_method' => 'pay_later',
        ])->assertSessionHasErrors(['pay_later_term_months', 'pay_later_confirm']);

        $this->assertDatabaseCount('pay_later_purchases', 0);
    }

    public function test_opening_due_payment_page_selects_simulated_paypal_without_charging_anything(): void
    {
        [$user, $purchase, $wallet] = $this->duePurchase(500000, 700000);

        $this->actingAs($user)->get(route('account.pay-later.pay', $purchase))
            ->assertOk()
            ->assertSee('PayPal')
            ->assertSee('mô phỏng')
            ->assertSee('checked', false);

        $this->assertSame('700000.00', $wallet->fresh()->balance);
        $this->assertSame('due', $purchase->fresh()->status);
        $this->assertDatabaseCount('billing_payment_attempts', 0);
    }

    public function test_customer_can_change_from_default_paypal_to_clare_pay_and_balance_changes_only_after_confirmation(): void
    {
        Notification::fake();
        [$user, $purchase, $wallet] = $this->duePurchase(500000, 700000);

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'clare_pay',
        ])->assertSessionHasErrors('confirm_payment');
        $this->assertSame('700000.00', $wallet->fresh()->balance);

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'clare_pay',
            'confirm_payment' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('200000.00', $wallet->fresh()->balance);
        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame('clare_pay', $purchase->fresh()->selected_payment_method);
        $this->assertSame('paid', $purchase->order->fresh()->payment_status);
        $this->assertDatabaseHas('clare_pay_transactions', ['type' => 'pay_later_payment', 'status' => 'completed']);
        Notification::assertSentTo($user, PayLaterNotice::class);
    }

    public function test_simulated_paypal_only_pays_after_the_customer_confirms(): void
    {
        Notification::fake();
        [$user, $purchase] = $this->duePurchase(500000, 0);

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'paypal',
        ])->assertSessionHasErrors('confirm_payment');
        $this->assertSame('due', $purchase->fresh()->status);
        $this->assertDatabaseCount('billing_payment_attempts', 0);

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'paypal',
            'confirm_payment' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertDatabaseHas('billing_payment_attempts', [
            'pay_later_purchase_id' => $purchase->getKey(),
            'provider' => 'paypal_simulated',
            'status' => 'paid',
        ]);
    }

    public function test_clare_pay_never_goes_negative_when_balance_is_insufficient(): void
    {
        [$user, $purchase, $wallet] = $this->duePurchase(500000, 100000);

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'clare_pay',
            'confirm_payment' => '1',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame('100000.00', $wallet->fresh()->balance);
        $this->assertSame('due', $purchase->fresh()->status);
        $this->assertDatabaseCount('clare_pay_transactions', 0);
    }

    public function test_payos_pay_later_is_not_paid_until_verified_webhook_arrives(): void
    {
        Notification::fake();
        [$user, $purchase] = $this->duePurchase(500000, 0);
        $this->fakePayOsCreate();

        $this->actingAs($user)->post(route('account.pay-later.pay.store', $purchase), [
            'payment_method' => 'payos',
            'confirm_payment' => '1',
        ])->assertRedirect('https://pay.payos.vn/web/BILLING');

        $attempt = BillingPaymentAttempt::query()->firstOrFail();
        $this->assertSame('processing', $purchase->fresh()->status);
        $this->assertSame('unpaid', $purchase->order->fresh()->payment_status);

        $this->fakePayOsWebhook($attempt, 'PAY-LATER-PAID');
        $this->postJson(route('webhooks.payos'), ['signature' => 'valid'])->assertOk();

        $this->assertSame('paid', $attempt->fresh()->status);
        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame('paid', $purchase->order->fresh()->payment_status);
    }

    public function test_payos_top_up_webhook_is_idempotent_and_redirect_alone_does_not_credit_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = ClarePayWallet::query()->create(['user_id' => $user->getKey(), 'balance' => 0]);
        $transaction = ClarePayTransaction::query()->create([
            'wallet_id' => $wallet->getKey(), 'type' => 'top_up', 'direction' => 'credit', 'amount' => 300000,
            'balance_before' => 0, 'balance_after' => 0, 'status' => 'pending', 'provider' => 'payos',
        ]);
        $attempt = BillingPaymentAttempt::query()->create([
            'user_id' => $user->getKey(), 'clare_pay_transaction_id' => $transaction->getKey(), 'purpose' => 'clare_pay_top_up',
            'provider' => 'payos', 'provider_reference' => '260915001', 'amount' => 300000, 'currency' => 'VND', 'status' => 'pending',
        ]);

        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('getPayment')->once()->andReturn(['status' => 'PENDING', 'amount' => 300000]);
        $this->app->instance(PayOsClient::class, $client);
        $this->actingAs($user)->get(route('billing.payos.return', ['orderCode' => '260915001']));
        $this->assertSame('0.00', $wallet->fresh()->balance);

        $this->fakePayOsWebhook($attempt, 'TOPUP-PAID', 300000, 2);
        $this->postJson(route('webhooks.payos'), ['signature' => 'valid'])->assertOk();
        $this->postJson(route('webhooks.payos'), ['signature' => 'valid'])->assertOk();

        $this->assertSame('300000.00', $wallet->fresh()->balance);
        $this->assertSame('completed', $transaction->fresh()->status);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_scheduler_updates_statuses_and_sends_each_reminder_once(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-09-15 10:00:00');
        [$monthUser, $monthPurchase] = $this->duePurchase(500000, 0);
        [$weekUser, $weekPurchase] = $this->duePurchase(500000, 0);
        [$dueUser, $duePurchase] = $this->duePurchase(500000, 0);
        [$overdueUser, $overduePurchase] = $this->duePurchase(500000, 0);
        $monthPurchase->update(['status' => 'active', 'due_at' => now()->addMonth()]);
        $weekPurchase->update(['status' => 'active', 'due_at' => now()->addDays(7)]);
        $duePurchase->update(['status' => 'active', 'due_at' => now()]);
        $overduePurchase->update(['status' => 'due', 'due_at' => now()->subDay()]);

        app(UpdatePayLaterStatusesAction::class)->execute();
        app(UpdatePayLaterStatusesAction::class)->execute();

        $this->assertSame('active', $monthPurchase->fresh()->status);
        $this->assertSame('due_soon', $weekPurchase->fresh()->status);
        $this->assertSame('due', $duePurchase->fresh()->status);
        $this->assertSame('overdue', $overduePurchase->fresh()->status);
        $this->assertDatabaseHas('pay_later_notification_events', ['pay_later_purchase_id' => $monthPurchase->getKey(), 'event_key' => 'month_before']);
        $this->assertDatabaseHas('pay_later_notification_events', ['pay_later_purchase_id' => $weekPurchase->getKey(), 'event_key' => 'seven_days_before']);
        $this->assertDatabaseHas('pay_later_notification_events', ['pay_later_purchase_id' => $duePurchase->getKey(), 'event_key' => 'due']);
        $this->assertDatabaseHas('pay_later_notification_events', ['pay_later_purchase_id' => $overduePurchase->getKey(), 'event_key' => 'overdue']);
        $this->assertDatabaseCount('pay_later_notification_events', 4);
        Notification::assertSentToTimes($monthUser, PayLaterNotice::class, 1);
        Notification::assertSentToTimes($weekUser, PayLaterNotice::class, 1);
        Notification::assertSentToTimes($dueUser, PayLaterNotice::class, 1);
        Notification::assertSentToTimes($overdueUser, PayLaterNotice::class, 1);
        $this->assertDatabaseHas('chat_messages', ['sender_type' => 'system']);
    }

    public function test_cancelling_order_also_cancels_pay_later_purchase(): void
    {
        [$user, $purchase] = $this->duePurchase(500000, 0);

        $this->actingAs($user)->post(route('account.orders.cancel', $purchase->order), [
            'cancel_reason' => 'Tôi không còn nhu cầu mua hàng',
            'confirm_cancel' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $purchase->fresh()->status);
        $this->assertSame('0.00', $purchase->fresh()->amount_due);
    }

    private function userWithCart(): User
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $cart = Cart::query()->create(['user_id' => $user->getKey(), 'currency' => 'VND', 'expires_at' => now()->addDay()]);
        $cart->items()->create([
            'product_variant_id' => ProductVariant::query()->where('is_active', true)->firstOrFail()->getKey(),
            'quantity' => 1,
            'is_selected' => true,
        ]);

        return $user;
    }

    private function duePurchase(int $amount, int $balance): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $order = $this->order($user, $amount);
        $payment = $order->payments()->create(['provider' => 'pay_later', 'amount' => $amount, 'currency' => 'VND', 'status' => 'unpaid']);
        $purchase = PayLaterPurchase::query()->create([
            'user_id' => $user->getKey(), 'order_id' => $order->getKey(), 'total_amount' => $amount,
            'term_months' => 2, 'starts_at' => now()->subMonths(2), 'due_at' => now(), 'amount_due' => $amount, 'status' => 'due',
        ]);
        $wallet = ClarePayWallet::query()->create(['user_id' => $user->getKey(), 'balance' => $balance]);

        return [$user, $purchase, $wallet, $payment];
    }

    private function order(User $user, int $amount): Order
    {
        return Order::query()->create([
            'number' => 'CLR-BNPL-'.Str::upper(Str::random(7)), 'user_id' => $user->getKey(), 'status' => 'pending',
            'payment_method' => 'pay_later', 'payment_status' => 'unpaid', 'currency' => 'VND',
            'customer_name' => $user->name, 'customer_email' => $user->email, 'customer_phone' => '0901234567',
            'shipping_recipient_name' => $user->name, 'shipping_phone' => '0901234567', 'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Bến Nghé', 'shipping_district' => 'Quận 1', 'shipping_city' => 'Hồ Chí Minh', 'shipping_country_code' => 'VN',
            'subtotal' => $amount, 'shipping_fee' => 0, 'discount_total' => 0, 'total' => $amount, 'placed_at' => now(),
        ]);
    }

    private function shippingAddress(): array
    {
        return [
            'shipping_recipient_name' => 'Nguyễn Minh An', 'shipping_phone' => '0901234567', 'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Bến Nghé', 'shipping_district' => 'Quận 1', 'shipping_city' => 'Hồ Chí Minh', 'shipping_country_code' => 'VN',
        ];
    }

    private function fakePayOsCreate(): void
    {
        config()->set('services.payos.enabled', true);
        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('createPayment')->once()->andReturnUsing(fn (array $data): array => [
            'orderCode' => $data['orderCode'], 'amount' => $data['amount'], 'paymentLinkId' => 'BILLING',
            'checkoutUrl' => 'https://pay.payos.vn/web/BILLING', 'qrCode' => 'QR',
        ]);
        $this->app->instance(PayOsClient::class, $client);
    }

    private function fakePayOsWebhook(BillingPaymentAttempt $attempt, string $reference, ?int $amount = null, int $times = 1): void
    {
        $client = \Mockery::mock(PayOsClient::class);
        $client->shouldReceive('verifyWebhook')->times($times)->andReturn([
            'orderCode' => (int) $attempt->provider_reference,
            'amount' => $amount ?? (int) $attempt->amount,
            'reference' => $reference,
            'paymentLinkId' => 'BILLING',
            'code' => '00',
            'desc' => 'success',
        ]);
        $this->app->instance(PayOsClient::class, $client);
    }
}
