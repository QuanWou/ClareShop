<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_guest_can_add_a_variant_and_see_vnd_subtotal(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();

        $response = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);

        $response
            ->assertRedirect(route('cart.show'))
            ->assertCookie(config('commerce.cart.cookie'))
            ->assertCookieNotExpired(config('commerce.cart.cookie'));

        $cart = Cart::query()->firstOrFail();

        $this->assertSame('VND', $cart->currency);
        $this->assertTrue(Str::isUuid($cart->guest_token));
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);

        $this->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Ru Đêm')
            ->assertSee('cart-purchase-card', false)
            ->assertSee('2.490.000 VND / sản phẩm')
            ->assertSee('4.980.000 VND')
            ->assertSee('Thành tiền dự kiến')
            ->assertSee('Tiến hành thanh toán')
            ->assertSee('Giỏ hàng, hiện có 2 sản phẩm');
    }

    public function test_adding_the_same_variant_increments_its_quantity(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRONZE')->firstOrFail();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);

        $cart = Cart::query()->firstOrFail();

        $this->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->getKey(),
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 3,
        ]);
    }

    public function test_adding_an_item_as_json_keeps_the_customer_on_the_current_page_and_returns_cart_count(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRASS')->firstOrFail();

        $this->postJson(route('cart.items.store'), [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Đã thêm sản phẩm vào giỏ hàng.')
            ->assertJsonPath('data.cart_item_count', 2)
            ->assertJsonPath('data.cart_url', route('cart.show'))
            ->assertCookie(config('commerce.cart.cookie'));

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);
    }

    public function test_unavailable_or_excess_stock_is_rejected_without_creating_an_empty_cart(): void
    {
        $outOfStock = ProductVariant::query()->where('sku', 'CLR-TM-CHARCOAL')->firstOrFail();
        $limitedStock = ProductVariant::query()->where('sku', 'CLR-TM-OLIVE')->firstOrFail();

        $this->from(route('catalog.products.show', 'thao-moc'))
            ->post(route('cart.items.store'), [
                'product_variant_id' => $outOfStock->getKey(),
                'quantity' => 1,
            ])
            ->assertRedirect(route('catalog.products.show', 'thao-moc'))
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('carts', 0);

        $this->from(route('catalog.products.show', 'thao-moc'))
            ->post(route('cart.items.store'), [
                'product_variant_id' => $limitedStock->getKey(),
                'quantity' => $limitedStock->stock_quantity + 1,
            ])
            ->assertRedirect(route('catalog.products.show', 'thao-moc'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_guest_can_update_and_remove_only_items_from_their_cart(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-CREAM')->firstOrFail();
        $ownedCart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $otherCart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $item = $ownedCart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);

        $this->withCookie(config('commerce.cart.cookie'), $otherCart->guest_token)
            ->patch(route('cart.items.update', $item), ['quantity' => 2])
            ->assertNotFound();

        $this->withCookie(config('commerce.cart.cookie'), $ownedCart->guest_token)
            ->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->getKey(),
            'quantity' => 3,
        ]);

        $this->withCookie(config('commerce.cart.cookie'), $ownedCart->guest_token)
            ->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseMissing('cart_items', ['id' => $item->getKey()]);
    }

    public function test_guest_cart_is_merged_into_the_authenticated_users_cart(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $guestCart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $guestCart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);
        $userCart = Cart::query()->create([
            'user_id' => $user->getKey(),
            'currency' => 'VND',
        ]);
        $userCart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->withCookie(config('commerce.cart.cookie'), $guestCart->guest_token)
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Giỏ hàng, hiện có 3 sản phẩm')
            ->assertCookieExpired(config('commerce.cart.cookie'));

        $this->assertDatabaseMissing('carts', ['id' => $guestCart->getKey()]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 3,
        ]);
    }

    public function test_customer_can_select_only_some_cart_items_for_checkout(): void
    {
        $customer = User::factory()->create(['is_active' => true]);
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
            'is_selected' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.checkout'), [
                'cart_item_ids' => [$selectedItem->getKey()],
            ])
            ->assertRedirect(route('checkout.show'));

        $this->assertTrue($selectedItem->fresh()->is_selected);
        $this->assertFalse($savedForLaterItem->fresh()->is_selected);

        $this->actingAs($customer)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee($selectedVariant->product->name)
            ->assertDontSee($savedForLaterVariant->product->name);
    }

    public function test_an_expired_guest_cookie_is_replaced_when_an_item_is_added(): void
    {
        $variant = ProductVariant::query()->where('sku', 'CLR-HH-BRASS')->firstOrFail();
        $expiredCart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->subMinute(),
        ]);

        $this->withCookie(config('commerce.cart.cookie'), $expiredCart->guest_token)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->getKey(),
                'quantity' => 1,
            ])
            ->assertRedirect(route('cart.show'))
            ->assertCookie(config('commerce.cart.cookie'))
            ->assertCookieNotExpired(config('commerce.cart.cookie'));

        $this->assertDatabaseCount('carts', 2);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);
    }
}
