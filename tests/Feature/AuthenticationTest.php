<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_guest_header_shows_login_and_registration_links(): void
    {
        $this->get(route('catalog.home'))
            ->assertOk()
            ->assertSee('Đăng nhập')
            ->assertSee('Đăng ký');
    }

    public function test_auth_pages_show_the_clare_brand_image_and_accessible_forms(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/catalog/login.png')
            ->assertSee('data-auth-form', false)
            ->assertSee('aria-controls="login-password"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('images/catalog/login.png')
            ->assertSee('data-password-feedback', false)
            ->assertSee('register-password-confirmation-error');
    }

    public function test_guest_is_redirected_to_login_before_viewing_account(): void
    {
        $this->get(route('account.show'))
            ->assertRedirect(route('login'));
    }

    public function test_registration_returns_the_customer_to_checkout_when_checkout_requested_login(): void
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('login'));

        $this->post(route('register.store'), [
            'name' => 'Nguyễn Minh An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasNoErrors();
    }

    public function test_an_inactive_customer_is_logged_out_before_sensitive_account_actions(): void
    {
        $customer = User::factory()->create(['is_active' => false]);

        $this->actingAs($customer)
            ->get(route('checkout.show'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_can_register_and_is_logged_in_as_a_customer(): void
    {
        $this->post(route('register.store'), [
            'name' => '  Nguyễn   Minh An  ',
            'email' => '  AN@EXAMPLE.TEST ',
            'phone' => '(090) 123-4567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'an@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Nguyễn Minh An', $user->name);
        $this->assertSame('0901234567', $user->phone);
        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_registration_rejects_invalid_contact_and_weak_password_data(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'A',
                'email' => 'khong-phai-email',
                'phone' => '012345',
                'password' => 'abcdefgh',
                'password_confirmation' => 'abcdefgh',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Nguyễn Minh An',
                'email' => 'an@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password456',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_customer_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'an@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => '  AN@EXAMPLE.TEST ',
            'password' => 'password123',
        ])
            ->assertRedirect(route('catalog.home'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('catalog.home'));

        $this->assertGuest();
    }

    public function test_inactive_customer_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'inactive@example.test',
                'password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cart_is_merged_after_a_real_login(): void
    {
        $user = User::factory()->create([
            'email' => 'an@example.test',
            'password' => 'password123',
        ]);
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

        $this->post(route('login.store'), [
            'email' => 'an@example.test',
            'password' => 'password123',
        ])->assertRedirect(route('catalog.home'));

        $this->withCookie(config('commerce.cart.cookie'), $guestCart->guest_token)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Giỏ hàng, hiện có 2 sản phẩm')
            ->assertCookieExpired(config('commerce.cart.cookie'));

        $this->assertDatabaseMissing('carts', ['id' => $guestCart->getKey()]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);
        $this->assertAuthenticatedAs($user);
    }
}
