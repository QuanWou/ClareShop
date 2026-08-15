@extends('layouts.admin', ['title' => 'Quản lý '.$user->name])

@section('content')
    <section class="admin-page" aria-labelledby="admin-user-edit-title">
        <a class="admin-back-link" href="{{ route('admin.users.index') }}">Trở về tài khoản</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Khách hàng &amp; quyền truy cập</p><h1 id="admin-user-edit-title">{{ $user->name }}</h1></div><p>{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</p></div>

        <div class="admin-user-summary"><article><span>Đơn hàng</span><strong>{{ $user->orders_count }}</strong></article><article><span>Yêu cầu dịch vụ</span><strong>{{ $user->appointments_count }}</strong></article><article><span>Tham gia</span><strong>{{ $user->created_at->format('d/m/Y') }}</strong></article></div>

        <form class="admin-promotion-form" method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PATCH')
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Thông tin cơ bản</p><h2>Hồ sơ &amp; liên hệ</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Họ và tên</span><input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"></label>
                    <label><span>Email</span><input name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></label>
                    <label><span>Số điện thoại</span><input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" inputmode="tel" autocomplete="tel"></label>
                </div>
            </section>
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Quyền truy cập</p><h2>Vai trò &amp; trạng thái</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Vai trò</span><select name="role" required><option value="customer" @selected(old('role', $user->role) === 'customer')>Khách hàng</option><option value="admin" @selected(old('role', $user->role) === 'admin')>Quản trị viên</option></select></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active))> Tài khoản đang hoạt động</span></label>
                    <label><span>Mật khẩu mới</span><input name="password" type="password" autocomplete="new-password"><small>Để trống nếu không đổi mật khẩu.</small></label>
                    <label><span>Xác nhận mật khẩu mới</span><input name="password_confirmation" type="password" autocomplete="new-password"></label>
                </div>
            </section>
            <p class="admin-promotion-audit">Không thể tự khóa/gỡ quyền của tài khoản đang đăng nhập và hệ thống luôn phải còn ít nhất một quản trị viên hoạt động.</p>
            <button class="admin-form-submit" type="submit">Lưu thay đổi</button>
        </form>

        <form class="admin-danger-zone admin-user-delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
            @csrf @method('DELETE')
            <div>
                <strong>Xóa tài khoản</strong>
                <p>Thao tác này xóa thông tin đăng nhập và liên hệ theo cơ chế an toàn, nhưng giữ nguyên snapshot đơn hàng và lịch sử vận hành. Không thể xóa chính tài khoản đang đăng nhập hoặc tài khoản còn đơn/yêu cầu đang xử lý.</p>
                <label class="admin-delete-confirmation"><input name="delete_confirmation" type="checkbox" value="1" required> Tôi hiểu và muốn xóa tài khoản này.</label>
            </div>
            <button type="submit">Xóa tài khoản</button>
        </form>
    </section>
@endsection
