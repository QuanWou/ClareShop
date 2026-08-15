@extends('layouts.admin', ['title' => 'Thêm tài khoản'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-user-create-title">
        <a class="admin-back-link" href="{{ route('admin.users.index') }}">Trở về tài khoản</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Khách hàng &amp; quyền truy cập</p><h1 id="admin-user-create-title">Thêm tài khoản.</h1></div><p>Tạo tài khoản khách hàng hoặc quản trị viên. Mật khẩu chỉ được hiển thị tại đây khi bạn nhập.</p></div>

        <form class="admin-promotion-form" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Thông tin cơ bản</p><h2>Hồ sơ &amp; liên hệ</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Họ và tên</span><input name="name" value="{{ old('name') }}" required autocomplete="name"></label>
                    <label><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
                    <label><span>Số điện thoại</span><input name="phone" type="tel" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel"></label>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Bảo mật &amp; quyền</p><h2>Quyền truy cập</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Mật khẩu tạm thời</span><input name="password" type="password" required autocomplete="new-password"><small>Tối thiểu 8 ký tự, có ít nhất một chữ và một số.</small></label>
                    <label><span>Xác nhận mật khẩu</span><input name="password_confirmation" type="password" required autocomplete="new-password"></label>
                    <label><span>Vai trò</span><select name="role" required><option value="customer" @selected(old('role', 'customer') === 'customer')>Khách hàng</option><option value="admin" @selected(old('role') === 'admin')>Quản trị viên</option></select></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', true))> Tài khoản đang hoạt động</span></label>
                </div>
            </section>
            <button class="admin-form-submit" type="submit">Tạo tài khoản</button>
        </form>
    </section>
@endsection
