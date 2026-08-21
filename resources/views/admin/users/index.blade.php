@extends('layouts.admin', ['title' => 'Quản lý khách hàng'])

@section('content')
    <section class="admin-page admin-customers-page" aria-labelledby="admin-users-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Khách hàng / Hồ sơ &amp; giá trị</p>
                <h1 id="admin-users-title">Khách hàng.</h1>
            </div>
            <div class="admin-page-heading-actions">
                <p>Theo dõi hồ sơ, lịch sử mua hàng, tổng chi tiêu và trạng thái truy cập trong cùng một khu vực quản trị.</p>
                <a class="admin-primary-link" href="{{ route('admin.users.create') }}">Thêm tài khoản</a>
            </div>
        </div>

        <form class="admin-filters admin-customer-filters" method="GET" action="{{ route('admin.users.index') }}">
            <label>
                <span>Tìm kiếm</span>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên, email hoặc số điện thoại">
            </label>
            <label>
                <span>Vai trò</span>
                <select name="role">
                    <option value="">Tất cả</option>
                    <option value="customer" @selected(($filters['role'] ?? '') === 'customer')>Khách hàng</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Quản trị viên</option>
                </select>
            </label>
            <label>
                <span>Trạng thái</span>
                <select name="status">
                    <option value="">Tất cả</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Đã khóa</option>
                </select>
            </label>
            <label>
                <span>Sắp xếp</span>
                <select name="sort">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Tài khoản mới nhất</option>
                    <option value="spent_desc" @selected(($filters['sort'] ?? '') === 'spent_desc')>Chi tiêu cao nhất</option>
                    <option value="orders_desc" @selected(($filters['sort'] ?? '') === 'orders_desc')>Nhiều đơn nhất</option>
                    <option value="last_order_desc" @selected(($filters['sort'] ?? '') === 'last_order_desc')>Mua gần đây</option>
                </select>
            </label>
            <button type="submit">Áp dụng</button>
        </form>

        <div class="admin-table-wrap admin-customer-table-wrap">
            <table class="admin-table admin-customer-table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Trạng thái</th>
                        <th>Tổng đơn</th>
                        <th>Tổng tiền đã mua</th>
                        <th>Hoạt động gần nhất</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="admin-customer-identity">
                                    <span class="admin-customer-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <span>{{ $user->email }}</span>
                                        @if ($user->phone)
                                            <small>{{ $user->phone }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="admin-status {{ $user->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $user->is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span>
                                <small class="admin-customer-role">{{ $user->role === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}</small>
                            </td>
                            <td>
                                <strong>{{ $user->orders_count }} đơn</strong>
                                <span>{{ $user->appointments_count }} yêu cầu tư vấn</span>
                            </td>
                            <td>
                                <strong class="admin-customer-spend">{{ \App\Modules\Shared\Support\Money::formatVnd($user->total_spent ?? 0) }}</strong>
                                <span>Chỉ tính đơn đã hoàn tất</span>
                            </td>
                            <td>
                                @if ($user->last_order_at)
                                    <strong>{{ $user->last_order_at->format('d/m/Y') }}</strong>
                                    <span>Đơn hàng gần nhất</span>
                                @else
                                    <span>Chưa có đơn hàng</span>
                                @endif
                                <small class="admin-customer-joined">Tham gia {{ $user->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td><a class="admin-record-link" href="{{ route('admin.users.edit', $user) }}">Xem hồ sơ</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Không tìm thấy khách hàng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="admin-pagination">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
