@extends('layouts.storefront', [
    'title' => 'Đăng nhập',
    'description' => 'Đăng nhập tài khoản Clare để theo dõi đơn hàng và yêu cầu hỗ trợ.',
    'bodyClass' => 'auth-screen',
    'inlineNotices' => true,
])

@section('content')
    <x-auth-shell
        eyebrow="Tài khoản Clare"
        heading-id="login-title"
        intro="Đăng nhập để tiếp tục hành trình chọn đèn, theo dõi đơn hàng và giữ mọi yêu cầu tư vấn trong một nơi."
        title="Chào mừng bạn trở lại."
        variant="login"
        visual-copy="Clare lưu lại những lựa chọn của bạn để hành trình từ một chiếc đèn đẹp đến căn phòng ấm luôn liền mạch."
        visual-eyebrow="Một khoảng sáng thân quen"
        :visual-items="['Giữ trọn giỏ hàng', 'Theo dõi từng đơn', 'Quản lý lịch tư vấn']"
        visual-title="Trở về với góc sáng của riêng bạn."
    >
        <form class="auth-form" action="{{ route('login.store') }}" method="POST" data-auth-form data-auth-mode="login">
            @csrf

            <div class="auth-field @error('email') has-error @enderror" data-auth-field>
                <label for="login-email">Email</label>
                <div class="auth-input-wrap">
                    <input
                        id="login-email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        inputmode="email"
                        maxlength="255"
                        placeholder="tenban@example.com"
                        aria-describedby="login-email-hint login-email-error"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        required
                        autofocus
                        data-auth-input
                    >
                    <span class="auth-field-status" aria-hidden="true"></span>
                </div>
                <p class="auth-field-hint" id="login-email-hint">Email bạn đã dùng khi tạo tài khoản Clare.</p>
                <p class="auth-field-error" id="login-email-error" data-field-error @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</p>
            </div>

            <div class="auth-field @error('password') has-error @enderror" data-auth-field>
                <label for="login-password">Mật khẩu</label>
                <div class="auth-input-wrap auth-password-wrap">
                    <input
                        id="login-password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        maxlength="255"
                        placeholder="Nhập mật khẩu"
                        aria-describedby="login-password-error"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        required
                        data-auth-input
                    >
                    <button class="auth-password-toggle" type="button" aria-controls="login-password" aria-label="Hiện mật khẩu" data-password-toggle>Hiện</button>
                </div>
                <p class="auth-field-error" id="login-password-error" data-field-error @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</p>
            </div>

            <label class="remember-field">
                <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                <span>Giữ đăng nhập trên thiết bị cá nhân này</span>
            </label>

            <button class="button button-primary button-wide auth-submit" type="submit" data-auth-submit>
                <span>Đăng nhập</span>
                <span aria-hidden="true">→</span>
            </button>
        </form>

        <p class="auth-security-note"><span aria-hidden="true"></span>Phiên đăng nhập được bảo vệ và giới hạn các lần thử không hợp lệ.</p>
        <p class="auth-switch">Chưa có tài khoản? <a href="{{ route('register') }}">Tạo tài khoản</a></p>
    </x-auth-shell>
@endsection
