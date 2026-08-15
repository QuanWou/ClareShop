@extends('layouts.admin', ['title' => 'Tài khoản'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-users-title">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Khách hàng &amp; quyền truy cập</p><h1 id="admin-users-title">Tài khoản.</h1></div><div class="admin-page-heading-actions"><p>Tạo mới, cập nhật hồ sơ, thay đổi quyền và xóa an toàn từng tài khoản. Lịch sử đơn hàng luôn được bảo toàn.</p><a class="admin-primary-link" href="{{ route('admin.users.create') }}">Thêm tài khoản</a></div></div>

        <form class="admin-filters" method="GET" action="{{ route('admin.users.index') }}">
            <label><span>Tìm kiếm</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên, email hoặc số điện thoại"></label>
            <label><span>Vai trò</span><select name="role"><option value="">Tất cả</option><option value="customer" @selected(($filters['role'] ?? '') === 'customer')>Khách hàng</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Quản trị viên</option></select></label>
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Đã khóa</option></select></label>
            <button type="submit">Lọc</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table"><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Hoạt động</th><th>Đơn / yêu cầu</th><th>Tham gia</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong><span>{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</span></td>
                            <td><span class="admin-status {{ $user->role === 'admin' ? 'admin-status-processing' : '' }}">{{ $user->role === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}</span></td>
                            <td><span class="admin-status {{ $user->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $user->is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span></td>
                            <td>{{ $user->orders_count }} đơn<span>{{ $user->appointments_count }} yêu cầu</span></td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td><a class="admin-record-link" href="{{ route('admin.users.edit', $user) }}">Quản lý</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Không tìm thấy tài khoản phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="admin-pagination">{{ $users->links() }}</div>@endif
    </section>
@endsection
