<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customers\Actions\ShowManagedUserAction;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customer_value_recent_orders_addresses_and_account_status(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create([
            'name' => 'Nguyễn An Nhiên',
            'email' => 'annhien@example.test',
            'phone' => '0901234567',
            'is_active' => false,
        ]);

        $customer->addresses()->create([
            'recipient_name' => 'Nguyễn An Nhiên',
            'phone' => '0901234567',
            'address_line_1' => '12 Nguyễn Huệ',
            'ward' => 'Phường Bến Nghé',
            'district' => 'Quận 1',
            'city' => 'Hồ Chí Minh',
            'country_code' => 'VN',
            'is_default' => true,
        ]);

        $this->order($customer, 'CLR-1001', 'completed', 1_200_000, now()->subDays(4));
        $this->order($customer, 'CLR-1002', 'completed', 2_300_000, now()->subDays(2));
        $this->order($customer, 'CLR-1003', 'pending', 900_000, now()->subDay());
        $this->order($customer, 'CLR-1004', 'cancelled', 7_000_000, now());

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'annhien@example.test']))
            ->assertOk()
            ->assertSee('Nguyễn An Nhiên')
            ->assertSee('4 đơn')
            ->assertSee('3.500.000 VND')
            ->assertSee('Đã khóa');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $customer))
            ->assertOk()
            ->assertSee('Tổng tiền đã mua')
            ->assertSee('3.500.000 VND')
            ->assertSee('Đơn gần đây')
            ->assertSeeInOrder(['CLR-1004', 'CLR-1003', 'CLR-1002', 'CLR-1001'])
            ->assertSee('12 Nguyễn Huệ')
            ->assertSee('Địa chỉ mặc định')
            ->assertSee('Đã khóa truy cập');

        $managedCustomer = app(ShowManagedUserAction::class)->execute($customer);

        $this->assertSame(4, $managedCustomer->orders_count);
        $this->assertSame(2, $managedCustomer->completed_orders_count);
        $this->assertSame('3500000.00', $managedCustomer->total_spent);
        $this->assertSame(1, $managedCustomer->addresses_count);
    }

    public function test_admin_can_sort_customers_by_completed_order_spend(): void
    {
        $admin = $this->admin();
        $lowerValueCustomer = User::factory()->create(['name' => 'Khách Hàng Nhẹ']);
        $higherValueCustomer = User::factory()->create(['name' => 'Khách Hàng Thân Thiết']);

        $this->order($lowerValueCustomer, 'CLR-2001', 'completed', 800_000, now()->subDay());
        $this->order($higherValueCustomer, 'CLR-2002', 'completed', 5_000_000, now());

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'customer', 'sort' => 'spent_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Khách Hàng Thân Thiết', 'Khách Hàng Nhẹ']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function order(User $customer, string $number, string $status, int $total, mixed $placedAt): Order
    {
        return Order::query()->create([
            'number' => $number,
            'user_id' => $customer->getKey(),
            'status' => $status,
            'payment_method' => 'cod',
            'payment_status' => $status === 'completed' ? 'paid' : 'unpaid',
            'currency' => 'VND',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone ?? '0900000000',
            'shipping_recipient_name' => $customer->name,
            'shipping_phone' => $customer->phone ?? '0900000000',
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Phường Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'shipping_country_code' => 'VN',
            'subtotal' => $total,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => $total,
            'placed_at' => $placedAt,
        ]);
    }
}
