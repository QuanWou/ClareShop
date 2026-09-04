<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\RecordPaymentStatusAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Actions\ExpirePendingVoucherReservationsAction;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoucherFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_public_voucher_shelf_hides_non_public_codes_and_guest_claim_returns_to_login(): void
    {
        $public = $this->voucher(['code' => 'PUBLIC10', 'is_public' => true]);
        $this->voucher(['code' => 'PRIVATE10', 'is_public' => false]);

        $this->get(route('promotions.index'))
            ->assertOk()
            ->assertSee('PUBLIC10')
            ->assertDontSee('PRIVATE10');

        $this->post(route('promotions.claim', $public))
            ->assertRedirect(route('login'))
            ->assertSessionHas('promotions.pending_claim_id', $public->getKey());
    }

    public function test_customer_can_claim_a_public_voucher_only_once(): void
    {
        $customer = $this->customer();
        $promotion = $this->voucher(['claim_limit' => 1]);

        $this->actingAs($customer)
            ->post(route('promotions.claim', $promotion))
            ->assertRedirect(route('promotions.index'));

        $this->actingAs($customer)
            ->post(route('promotions.claim', $promotion))
            ->assertRedirect(route('promotions.index'));

        $this->assertDatabaseCount('user_vouchers', 1);
        $this->assertSame(1, $promotion->fresh()->claim_count);
    }

    public function test_claim_required_voucher_is_reserved_at_checkout_and_redeemed_only_after_payment_is_confirmed(): void
    {
        $customer = $this->customer();
        $promotion = $this->voucher([
            'code' => 'WALLET10',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'requires_claim' => true,
            'usage_limit' => 1,
        ]);
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();

        $unclaimedCart = $this->cartWith($variant);
        $response = $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $unclaimedCart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), $this->checkoutPayload('momo', $promotion->code));

        $response->assertUnprocessable()->assertJsonValidationErrors('discount_code');

        $this->actingAs($customer)->post(route('promotions.claim', $promotion));
        $cart = $this->cartWith($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), $this->checkoutPayload('momo', $promotion->code))
            ->assertCreated();

        $order = Order::query()->firstOrFail();
        $walletVoucher = UserVoucher::query()->firstOrFail();

        $this->assertSame(0, $promotion->fresh()->usage_count);
        $this->assertSame(0, $walletVoucher->fresh()->used_count);
        $this->assertDatabaseHas('voucher_reservations', [
            'order_id' => $order->getKey(),
            'user_voucher_id' => $walletVoucher->getKey(),
            'status' => 'reserved',
        ]);

        app(RecordPaymentStatusAction::class)->execute(
            $order,
            $order->payments()->firstOrFail(),
            $customer->getKey(),
            'paid',
            'Đã đối soát chuyển khoản thử nghiệm.',
        );

        $this->assertSame(1, $promotion->fresh()->usage_count);
        $this->assertSame(1, $walletVoucher->fresh()->used_count);
        $this->assertDatabaseHas('voucher_reservations', [
            'order_id' => $order->getKey(),
            'status' => 'redeemed',
        ]);
    }

    public function test_expired_pending_voucher_reservation_cancels_order_and_releases_voucher(): void
    {
        $customer = $this->customer();
        $promotion = $this->voucher(['code' => 'HOLD30', 'requires_claim' => true]);
        $this->actingAs($customer)->post(route('promotions.claim', $promotion));
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();
        $stockBefore = $variant->stock_quantity;
        $cart = $this->cartWith($variant);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->withCredentials()
            ->postJson(route('checkout.orders.store'), $this->checkoutPayload('momo', $promotion->code))
            ->assertCreated();

        $order = Order::query()->firstOrFail();
        $this->travel(31)->minutes();

        $this->assertSame(1, app(ExpirePendingVoucherReservationsAction::class)->execute());
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame($stockBefore, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('voucher_reservations', [
            'order_id' => $order->getKey(),
            'status' => 'released',
        ]);
    }

    public function test_customer_cannot_open_or_select_another_customers_voucher(): void
    {
        $owner = $this->customer();
        $other = User::factory()->create(['is_active' => true]);
        $promotion = $this->voucher();
        $this->actingAs($owner)->post(route('promotions.claim', $promotion));
        $voucher = UserVoucher::query()->firstOrFail();

        $this->actingAs($other)
            ->post(route('account.vouchers.use', $voucher))
            ->assertNotFound();
    }

    private function voucher(array $attributes = []): PromotionCode
    {
        return PromotionCode::query()->create([
            'code' => 'CLARE-'.Str::upper(Str::random(6)),
            'name' => 'Ưu đãi Clare',
            'description' => 'Ưu đãi thử nghiệm.',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 100000,
            'usage_limit' => 10,
            'claim_limit' => 10,
            'per_user_usage_limit' => 1,
            'is_active' => true,
            'is_public' => true,
            'requires_claim' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            ...$attributes,
        ]);
    }

    private function cartWith(ProductVariant $variant): Cart
    {
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $cart->items()->create(['product_variant_id' => $variant->getKey(), 'quantity' => 1]);

        return $cart;
    }

    private function checkoutPayload(string $paymentMethod, string $discountCode): array
    {
        return [
            'shipping_recipient_name' => 'Nguyễn Minh An',
            'shipping_phone' => '0901234567',
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Phường Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'shipping_country_code' => 'VN',
            'customer_name' => 'Nguyễn Minh An',
            'customer_phone' => '0901234567',
            'payment_method' => $paymentMethod,
            'discount_code' => $discountCode,
        ];
    }

    private function customer(): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Minh An',
            'email' => Str::lower(Str::random(8)).'@example.test',
            'phone' => '0901234567',
            'is_active' => true,
        ]);
    }
}
