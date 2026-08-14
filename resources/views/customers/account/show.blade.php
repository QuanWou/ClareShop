@extends('layouts.storefront', [
    'title' => 'Tài khoản',
    'description' => 'Quản lý thông tin, địa chỉ, đơn hàng và yêu cầu dịch vụ của bạn tại Clare.',
])

@section('content')
    <section class="account-page section" aria-labelledby="account-title">
        <div class="shell account-shell">
            <div class="account-heading">
                <div>
                    <p class="eyebrow">Tài khoản Clare</p>
                    <h1 id="account-title">Một nơi cho<br>mọi điều của bạn.</h1>
                </div>
                <p>Kiểm tra đơn hàng, lưu địa chỉ nhận hàng và chủ động quản lý thông tin đăng nhập của bạn.</p>
            </div>

            <div class="account-dashboard">
                <aside class="account-profile-rail" aria-label="Điều hướng tài khoản">
                    <div class="account-profile-summary">
                        <p class="eyebrow">Hồ sơ</p>
                        <h2>{{ $user->name }}</h2>
                        <dl>
                            <div>
                                <dt>Email đăng nhập</dt>
                                <dd>{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt>Số điện thoại</dt>
                                <dd>{{ $user->phone ?? 'Chưa cập nhật' }}</dd>
                            </div>
                            <div>
                                <dt>Thành viên từ</dt>
                                <dd>{{ $user->created_at?->format('d/m/Y') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <nav class="account-profile-nav" aria-label="Nội dung tài khoản">
                        <a href="#account-orders">Đơn hàng <span>{{ $orderCount }}</span></a>
                        <a href="#account-services">Yêu cầu hỗ trợ <span>{{ $appointmentCount }}</span></a>
                        <a href="#account-profile">Thông tin cá nhân</a>
                        <a href="#account-address">Địa chỉ nhận hàng</a>
                        <a href="#account-security">Bảo mật</a>
                        <a href="#account-support">Liên hệ Clare</a>
                    </nav>

                    <div class="account-profile-actions">
                        @if ($user->isAdmin())
                            <a class="account-admin-link" href="{{ route('admin.dashboard') }}">Mở quản trị</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit">Đăng xuất</button>
                        </form>
                    </div>
                </aside>

                <div class="account-content">
                    <section class="account-overview" aria-labelledby="account-overview-title">
                        <div>
                            <p class="eyebrow">Tổng quan</p>
                            <h2 id="account-overview-title">Chào {{ $user->name }}.</h2>
                        </div>
                        <p>Mọi đơn hàng đều gắn với tài khoản này để bạn theo dõi tiến độ và nhận hỗ trợ dễ dàng hơn.</p>

                        <div class="account-stat-grid">
                            <div>
                                <strong>{{ $orderCount }}</strong>
                                <span>đơn đã tạo</span>
                            </div>
                            <div>
                                <strong>{{ $appointmentCount }}</strong>
                                <span>yêu cầu dịch vụ</span>
                            </div>
                            <div>
                                <strong>{{ $defaultAddress ? 'Đã lưu' : 'Chưa lưu' }}</strong>
                                <span>địa chỉ mặc định</span>
                            </div>
                        </div>
                    </section>

                    <section class="account-panel" id="account-orders" aria-labelledby="account-orders-title">
                        <div class="account-panel-heading">
                            <div>
                                <p class="eyebrow">Đơn hàng</p>
                                <h2 id="account-orders-title">Các đơn đã đặt</h2>
                            </div>
                            <a href="{{ route('catalog.products.index') }}">Tiếp tục mua sắm</a>
                        </div>

                        <div class="account-record-list">
                            @forelse ($orders as $order)
                                <a class="account-record" href="{{ route('account.orders.show', $order) }}">
                                    <div>
                                        <strong>{{ $order->number }}</strong>
                                        <span>{{ $order->placed_at?->format('d/m/Y') }} · {{ $order->statusLabel() }} · Xem theo dõi</span>
                                    </div>
                                    <b>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</b>
                                </a>
                            @empty
                                <p class="account-empty">Bạn chưa có đơn nào. Hãy chọn mẫu đèn yêu thích, thêm vào giỏ và hoàn tất checkout bằng tài khoản này.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="account-panel" id="account-services" aria-labelledby="account-appointments-title">
                        <div class="account-panel-heading">
                            <div>
                                <p class="eyebrow">Dịch vụ</p>
                                <h2 id="account-appointments-title">Yêu cầu hỗ trợ</h2>
                            </div>
                            <a href="{{ route('appointments.create') }}">Tạo yêu cầu</a>
                        </div>

                        <div class="account-record-list">
                            @forelse ($appointments as $appointment)
                                <article class="account-record">
                                    <div>
                                        <strong>{{ $appointment->typeLabel() }}</strong>
                                        <span>{{ $appointment->number }} · {{ $appointment->preferred_starts_at->format('H:i, d/m/Y') }}</span>
                                    </div>
                                    <b>{{ $appointment->statusLabel() }}</b>
                                </article>
                            @empty
                                <p class="account-empty">Bạn chưa gửi yêu cầu nào. Clare luôn sẵn sàng hỗ trợ chọn đèn hoặc ghi nhận nhu cầu lắp đặt.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="account-panel" id="account-profile" aria-labelledby="account-profile-title">
                        <div class="account-panel-heading">
                            <div>
                                <p class="eyebrow">Thông tin cá nhân</p>
                                <h2 id="account-profile-title">Thông tin liên hệ</h2>
                            </div>
                            <p>Đổi email cần xác nhận bằng mật khẩu hiện tại.</p>
                        </div>

                        <form class="account-form" action="{{ route('account.profile.update') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <div class="account-form-grid">
                                <label class="account-field account-field-full">
                                    <span>Họ và tên</span>
                                    <input name="name" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="80" required>
                                    @error('name') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Email đăng nhập</span>
                                    <input name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required>
                                    @error('email') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Số điện thoại <small>Không bắt buộc</small></span>
                                    <input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel" maxlength="30" placeholder="0901234567">
                                    @error('phone') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field account-field-full">
                                    <span>Mật khẩu hiện tại <small>Chỉ cần khi đổi email</small></span>
                                    <input name="profile_current_password" type="password" autocomplete="current-password">
                                    @error('profile_current_password') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>
                            </div>

                            <button class="button button-primary" type="submit">Lưu thông tin</button>
                        </form>
                    </section>

                    <section class="account-panel" id="account-address" aria-labelledby="account-address-title">
                        <div class="account-panel-heading">
                            <div>
                                <p class="eyebrow">Giao hàng</p>
                                <h2 id="account-address-title">Địa chỉ nhận hàng mặc định</h2>
                            </div>
                            <p>Địa chỉ này sẽ tự điền ở checkout; bạn vẫn có thể đổi cho từng đơn.</p>
                        </div>

                        <form class="account-form" action="{{ route('account.address.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="account-form-grid">
                                <label class="account-field account-field-full">
                                    <span>Tên người nhận</span>
                                    <input name="recipient_name" value="{{ old('recipient_name', $defaultAddress?->recipient_name ?? $user->name) }}" autocomplete="shipping name" maxlength="255" required>
                                    @error('recipient_name') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Số điện thoại nhận hàng</span>
                                    <input name="address_phone" type="tel" value="{{ old('address_phone', $defaultAddress?->phone ?? $user->phone) }}" autocomplete="shipping tel" maxlength="30" required>
                                    @error('address_phone') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Tỉnh / Thành phố</span>
                                    <input name="city" value="{{ old('city', $defaultAddress?->city) }}" autocomplete="shipping address-level1" maxlength="255" required>
                                    @error('city') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field account-field-full">
                                    <span>Địa chỉ</span>
                                    <input name="address_line_1" value="{{ old('address_line_1', $defaultAddress?->address_line_1) }}" autocomplete="shipping street-address" maxlength="255" placeholder="Số nhà, tên đường" required>
                                    @error('address_line_1') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Phường / Xã</span>
                                    <input name="ward" value="{{ old('ward', $defaultAddress?->ward) }}" maxlength="255" required>
                                    @error('ward') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Quận / Huyện</span>
                                    <input name="district" value="{{ old('district', $defaultAddress?->district) }}" maxlength="255" required>
                                    @error('district') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Mã bưu chính <small>Không bắt buộc</small></span>
                                    <input name="postal_code" value="{{ old('postal_code', $defaultAddress?->postal_code) }}" autocomplete="shipping postal-code" maxlength="20">
                                    @error('postal_code') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Địa chỉ bổ sung <small>Không bắt buộc</small></span>
                                    <input name="address_line_2" value="{{ old('address_line_2', $defaultAddress?->address_line_2) }}" autocomplete="shipping address-line2" maxlength="255">
                                    @error('address_line_2') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>
                            </div>

                            <input name="country_code" type="hidden" value="VN">
                            <button class="button button-primary" type="submit">Lưu địa chỉ mặc định</button>
                        </form>
                    </section>

                    <section class="account-panel" id="account-security" aria-labelledby="account-security-title">
                        <div class="account-panel-heading">
                            <div>
                                <p class="eyebrow">Bảo mật</p>
                                <h2 id="account-security-title">Đổi mật khẩu</h2>
                            </div>
                            <p>Dùng mật khẩu có chữ và số, tối thiểu 8 ký tự.</p>
                        </div>

                        <form class="account-form" action="{{ route('account.password.update') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <div class="account-form-grid">
                                <label class="account-field account-field-full">
                                    <span>Mật khẩu hiện tại</span>
                                    <input name="password_current_password" type="password" autocomplete="current-password" required>
                                    @error('password_current_password') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Mật khẩu mới</span>
                                    <input name="password" type="password" autocomplete="new-password" required>
                                    @error('password') <small class="account-field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="account-field">
                                    <span>Xác nhận mật khẩu mới</span>
                                    <input name="password_confirmation" type="password" autocomplete="new-password" required>
                                </label>
                            </div>

                            <button class="button button-primary" type="submit">Cập nhật mật khẩu</button>
                        </form>
                    </section>

                    <section class="account-support-panel" id="account-support" aria-labelledby="account-support-title">
                        <div>
                            <p class="eyebrow">Cần Clare hỗ trợ?</p>
                            <h2 id="account-support-title">Chúng mình luôn sẵn sàng lắng nghe.</h2>
                            <p>Gửi yêu cầu tư vấn chọn đèn, hỗ trợ lắp đặt hoặc liên hệ trực tiếp để được hướng dẫn về đơn hàng.</p>
                        </div>
                        <div class="account-support-actions">
                            <a class="button button-primary" href="{{ route('appointments.create', ['type' => 'consultation']) }}">Tư vấn chọn đèn</a>
                            <a class="button button-secondary" href="{{ route('appointments.create', ['type' => 'installation']) }}">Yêu cầu lắp đặt</a>
                            <a class="account-email-link" href="mailto:hello@clare.local">hello@clare.local</a>
                        </div>
                    </section>

                    <section class="account-danger-panel" aria-labelledby="account-danger-title">
                        <div>
                            <p class="eyebrow">Vùng cần cân nhắc</p>
                            <h2 id="account-danger-title">Xóa tài khoản</h2>
                            <p>Thao tác này đăng xuất khỏi mọi phiên, xóa hồ sơ và địa chỉ đã lưu. Các snapshot đơn hàng hoặc yêu cầu đã hoàn tất vẫn được giữ để đảm bảo lịch sử vận hành.</p>
                        </div>

                        <form class="account-delete-form" action="{{ route('account.destroy') }}" method="POST">
                            @csrf
                            @method('DELETE')

                            <label class="account-field">
                                <span>Mật khẩu hiện tại</span>
                                <input name="deletion_current_password" type="password" autocomplete="current-password" required>
                                @error('deletion_current_password') <small class="account-field-error">{{ $message }}</small> @enderror
                            </label>

                            <label class="account-field">
                                <span>Nhập <strong>XOA TAI KHOAN</strong> để xác nhận</span>
                                <input name="confirmation" autocomplete="off" required>
                                @error('confirmation') <small class="account-field-error">{{ $message }}</small> @enderror
                            </label>

                            @error('account_deletion') <p class="account-field-error">{{ $message }}</p> @enderror

                            <button class="account-delete-button" type="submit">Xóa tài khoản của tôi</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
