<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_customer_can_view_their_expanded_account_dashboard(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Hồ sơ')
            ->assertSee('Thông tin cá nhân')
            ->assertSee('Địa chỉ nhận hàng mặc định')
            ->assertSee('Đổi mật khẩu')
            ->assertSee('Xóa tài khoản');
    }

    public function test_customer_can_update_their_profile_and_must_confirm_before_changing_login_email(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->from(route('account.show'))
            ->patch(route('account.profile.update'), [
                'name' => 'Nguyễn Mai An',
                'email' => 'mai@example.test',
                'phone' => '0901234568',
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasErrors('profile_current_password');

        $this->actingAs($customer)
            ->patch(route('account.profile.update'), [
                'name' => '  Nguyễn   Mai An ',
                'email' => '  MAI@EXAMPLE.TEST ',
                'phone' => '(090) 123-4568',
                'profile_current_password' => 'password123',
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $customer->getKey(),
            'name' => 'Nguyễn Mai An',
            'email' => 'mai@example.test',
            'phone' => '0901234568',
        ]);
    }

    public function test_customer_can_save_a_default_address_and_it_prefills_checkout(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->put(route('account.address.update'), [
                ...$this->address(),
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $customer->getKey(),
            'recipient_name' => 'Nguyễn Minh An',
            'phone' => '0901234567',
            'city' => 'Hồ Chí Minh',
            'is_default' => true,
        ]);

        $variant = ProductVariant::query()->where('sku', 'CLR-RD-BURGUNDY')->firstOrFail();
        $cart = Cart::query()->create([
            'guest_token' => (string) Str::uuid(),
            'currency' => 'VND',
            'expires_at' => now()->addDay(),
        ]);
        $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);

        $this->actingAs($customer)
            ->withCookie(config('commerce.cart.cookie'), $cart->guest_token)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('12 Nguyễn Huệ')
            ->assertSee('Phường Bến Nghé')
            ->assertSee('Hồ Chí Minh');
    }

    public function test_customer_can_change_their_password(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->patch(route('account.password.update'), [
                'password_current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newpassword456', $customer->fresh()->password));
    }

    public function test_customer_can_delete_their_account_and_their_profile_is_anonymized(): void
    {
        $customer = $this->customer();
        $customer->addresses()->create([
            'recipient_name' => 'Nguyễn Minh An',
            'phone' => '0901234567',
            'address_line_1' => '12 Nguyễn Huệ',
            'address_line_2' => 'Tầng 8',
            'ward' => 'Phường Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'postal_code' => '700000',
            'country_code' => 'VN',
            'is_default' => true,
        ]);

        $this->actingAs($customer)
            ->delete(route('account.destroy'), [
                'deletion_current_password' => 'password123',
                'confirmation' => 'XOA TAI KHOAN',
            ])
            ->assertRedirect(route('catalog.home'))
            ->assertSessionHasNoErrors();

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $customer->getKey()]);
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $customer->getKey()]);

        $deletedCustomer = User::withTrashed()->findOrFail($customer->getKey());
        $this->assertSame('Tài khoản đã xóa', $deletedCustomer->name);
        $this->assertNull($deletedCustomer->phone);
        $this->assertFalse($deletedCustomer->is_active);
    }

    public function test_the_last_active_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->customer([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('account.show'))
            ->delete(route('account.destroy'), [
                'deletion_current_password' => 'password123',
                'confirmation' => 'XOA TAI KHOAN',
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHasErrors('account_deletion');

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('users', ['id' => $admin->getKey(), 'deleted_at' => null]);
    }

    private function customer(array $attributes = []): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Minh An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
            'password' => 'password123',
            ...$attributes,
        ]);
    }

    private function address(): array
    {
        return [
            'recipient_name' => 'Nguyễn Minh An',
            'address_phone' => '0901234567',
            'address_line_1' => '12 Nguyễn Huệ',
            'address_line_2' => 'Tầng 8',
            'ward' => 'Phường Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'postal_code' => '700000',
            'country_code' => 'VN',
        ];
    }
}
