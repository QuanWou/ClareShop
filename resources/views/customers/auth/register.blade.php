@extends('layouts.storefront', [
    'title' => 'Tạo tài khoản',
    'description' => 'Tạo tài khoản khách hàng Clare.',
    'bodyClass' => 'auth-screen',
    'inlineNotices' => true,
])

@section('content')
    <x-auth-shell
        eyebrow="Thành viên mới"
        heading-id="register-title"
        intro="Tạo tài khoản để đặt hàng an toàn, theo dõi từng chặng giao và lưu những yêu cầu tư vấn dành riêng cho không gian của bạn."
        title="Bắt đầu một khoảng sáng mới."
        variant="register"
        visual-copy="Một tài khoản nhỏ giúp Clare nhớ đúng chiếc đèn, đúng màu và những điều bạn cần cho căn phòng của mình."
        visual-eyebrow="Bắt đầu cùng Clare"
        :visual-items="['Đặt hàng liền mạch', 'Theo dõi tiến độ giao', 'Nhận hỗ trợ đúng nhu cầu']"
        visual-title="Để mỗi buổi tối có thêm một khoảng dịu dàng."
    >
        <form class="auth-form" action="{{ route('register.store') }}" method="POST" data-auth-form data-auth-mode="register">
            @csrf

            <div class="auth-field @error('name') has-error @enderror" data-auth-field>
                <label for="register-name">Họ và tên</label>
                <div class="auth-input-wrap">
                    <input
                        id="register-name"
                        name="name"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        minlength="2"
                        maxlength="80"
                        placeholder="Nguyễn Minh An"
                        aria-describedby="register-name-error"
                        aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                        required
                        autofocus
                        data-auth-input
                    >
                    <span class="auth-field-status" aria-hidden="true"></span>
                </div>
                <p class="auth-field-error" id="register-name-error" data-field-error @if (! $errors->has('name')) hidden @endif>{{ $errors->first('name') }}</p>
            </div>

            <div class="auth-field @error('email') has-error @enderror" data-auth-field>
                <label for="register-email">Email</label>
                <div class="auth-input-wrap">
                    <input
                        id="register-email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        inputmode="email"
                        maxlength="255"
                        placeholder="tenban@example.com"
                        aria-describedby="register-email-hint register-email-error"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        required
                        data-auth-input
                    >
                    <span class="auth-field-status" aria-hidden="true"></span>
                </div>
                <p class="auth-field-hint" id="register-email-hint">Dùng email bạn thường xuyên truy cập để theo dõi đơn.</p>
                <p class="auth-field-error" id="register-email-error" data-field-error @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</p>
            </div>

            <div class="auth-field @error('phone') has-error @enderror" data-auth-field>
                <div class="auth-label-row">
                    <label for="register-phone">Số điện thoại</label>
                    <span>Không bắt buộc</span>
                </div>
                <div class="auth-input-wrap">
                    <input
                        id="register-phone"
                        name="phone"
                        type="tel"
                        value="{{ old('phone') }}"
                        autocomplete="tel"
                        inputmode="tel"
                        maxlength="20"
                        placeholder="090 123 4567"
                        aria-describedby="register-phone-hint register-phone-error"
                        aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                        data-auth-input
                        data-vn-phone
                    >
                    <span class="auth-field-status" aria-hidden="true"></span>
                </div>
                <p class="auth-field-hint" id="register-phone-hint">Chấp nhận số Việt Nam bắt đầu bằng 0 hoặc +84.</p>
                <p class="auth-field-error" id="register-phone-error" data-field-error @if (! $errors->has('phone')) hidden @endif>{{ $errors->first('phone') }}</p>
            </div>

            <div class="auth-field @error('password') has-error @enderror" data-auth-field>
                <label for="register-password">Mật khẩu</label>
                <div class="auth-input-wrap auth-password-wrap">
                    <input
                        id="register-password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="255"
                        placeholder="Tạo mật khẩu"
                        aria-describedby="register-password-requirements register-password-error"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        required
                        data-auth-input
                        data-password-source
                    >
                    <button class="auth-password-toggle" type="button" aria-controls="register-password" aria-label="Hiện mật khẩu" data-password-toggle>Hiện</button>
                </div>
                <div class="auth-password-feedback" id="register-password-requirements" data-password-feedback aria-live="polite">
                    <div class="auth-strength-meter" role="progressbar" aria-label="Độ mạnh mật khẩu" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" data-password-meter>
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="auth-requirements">
                        <span data-password-rule="length">8+ ký tự</span>
                        <span data-password-rule="letter">Có chữ cái</span>
                        <span data-password-rule="number">Có chữ số</span>
                    </div>
                </div>
                <p class="auth-field-error" id="register-password-error" data-field-error @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</p>
            </div>

            <div class="auth-field" data-auth-field>
                <label for="register-password-confirmation">Xác nhận mật khẩu</label>
                <div class="auth-input-wrap auth-password-wrap">
                    <input
                        id="register-password-confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="255"
                        placeholder="Nhập lại mật khẩu"
                        aria-describedby="register-password-confirmation-error"
                        aria-invalid="false"
                        required
                        data-auth-input
                        data-password-confirmation
                    >
                    <button class="auth-password-toggle" type="button" aria-controls="register-password-confirmation" aria-label="Hiện mật khẩu xác nhận" data-password-toggle>Hiện</button>
                </div>
                <p class="auth-field-error" id="register-password-confirmation-error" data-field-error hidden></p>
            </div>

            <button class="button button-primary button-wide auth-submit" type="submit" data-auth-submit>
                <span>Tạo tài khoản</span>
                <span aria-hidden="true">→</span>
            </button>
        </form>

        <p class="auth-security-note"><span aria-hidden="true"></span>Mật khẩu được mã hóa; Clare không lưu mật khẩu ở dạng có thể đọc.</p>
        <p class="auth-switch">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </x-auth-shell>
@endsection
