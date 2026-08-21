@extends('layouts.admin', ['title' => 'Khách hàng '.$user->name])

@section('content')
    <section class="admin-page admin-customer-detail-page" aria-labelledby="admin-user-edit-title">
        <a class="admin-back-link" href="{{ route('admin.users.index') }}">Trở về khách hàng</a>

        <div class="admin-customer-detail-heading">
            <div class="admin-customer-detail-identity">
                <span class="admin-customer-avatar admin-customer-avatar-large" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div>
                    <p class="admin-eyebrow">Hồ sơ khách hàng #{{ $user->getKey() }}</p>
                    <h1 id="admin-user-edit-title">{{ $user->name }}</h1>
                    <p>{{ $user->email }}@if ($user->phone) <span aria-hidden="true">·</span> {{ $user->phone }}@endif</p>
                </div>
            </div>
            <div class="admin-customer-detail-statuses" aria-label="Trạng thái tài khoản">
                <span class="admin-status {{ $user->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $user->is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span>
                <span class="admin-status {{ $user->role === 'admin' ? 'admin-status-processing' : '' }}">{{ $user->role === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}</span>
            </div>
        </div>

        <div class="admin-user-summary admin-customer-summary" aria-label="Tổng quan khách hàng">
            <article>
                <span>Tổng số đơn</span>
                <strong>{{ $user->orders_count }}</strong>
                <small>Mọi trạng thái đơn hàng</small>
            </article>
            <article>
                <span>Đơn hoàn tất</span>
                <strong>{{ $user->completed_orders_count }}</strong>
                <small>Đã ghi nhận giao thành công</small>
            </article>
            <article class="admin-customer-summary-spend">
                <span>Tổng tiền đã mua</span>
                <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($user->total_spent ?? 0) }}</strong>
                <small>Chỉ cộng các đơn đã hoàn tất</small>
            </article>
            <article>
                <span>Địa chỉ đã lưu</span>
                <strong>{{ $user->addresses_count }}</strong>
                <small>{{ $user->appointments_count }} yêu cầu tư vấn/lắp đặt</small>
            </article>
        </div>

        <div class="admin-customer-detail-grid">
            <section class="admin-panel admin-customer-orders" aria-labelledby="customer-recent-orders-title">
                <div class="admin-panel-heading">
                    <div>
                        <p class="admin-eyebrow">Lịch sử mua hàng</p>
                        <h2 id="customer-recent-orders-title">Đơn gần đây</h2>
                    </div>
                    @if ($user->orders_count > 6)
                        <a href="{{ route('admin.orders.index', ['q' => $user->email]) }}">Xem toàn bộ</a>
                    @endif
                </div>

                <div class="admin-customer-order-list">
                    @forelse ($user->orders as $order)
                        <article>
                            <div class="admin-customer-order-main">
                                <a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a>
                                <p>{{ $order->items_count }} sản phẩm <span aria-hidden="true">·</span> {{ $order->placed_at?->format('H:i, d/m/Y') ?? 'Chưa ghi nhận thời gian' }}</p>
                            </div>
                            <div class="admin-customer-order-status">
                                <span class="admin-status admin-status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                                <small>{{ $order->paymentStatusLabel() }}</small>
                            </div>
                            <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>
                            <a class="admin-customer-order-open" href="{{ route('admin.orders.show', $order) }}" aria-label="Mở đơn {{ $order->number }}">↗</a>
                        </article>
                    @empty
                        <div class="admin-customer-empty-state">
                            <span aria-hidden="true">01</span>
                            <div><strong>Chưa có đơn hàng.</strong><p>Lịch sử mua hàng sẽ xuất hiện ở đây sau khi khách đặt đơn đầu tiên.</p></div>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="admin-customer-sidebar" aria-label="Thông tin khách hàng">
                <section class="admin-panel admin-customer-profile-card" aria-labelledby="customer-profile-title">
                    <div class="admin-panel-heading">
                        <div><p class="admin-eyebrow">Thông tin khách</p><h2 id="customer-profile-title">Hồ sơ &amp; liên hệ</h2></div>
                    </div>
                    <dl class="admin-customer-profile-list">
                        <div><dt>Họ và tên</dt><dd>{{ $user->name }}</dd></div>
                        <div><dt>Email</dt><dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd></div>
                        <div><dt>Điện thoại</dt><dd>@if ($user->phone)<a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>@else Chưa cập nhật @endif</dd></div>
                        <div><dt>Ngày tham gia</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
                        <div><dt>Trạng thái</dt><dd>{{ $user->is_active ? 'Đang hoạt động' : 'Đã khóa truy cập' }}</dd></div>
                    </dl>
                </section>

                <section class="admin-panel admin-customer-address-card" aria-labelledby="customer-addresses-title">
                    <div class="admin-panel-heading">
                        <div><p class="admin-eyebrow">Sổ địa chỉ</p><h2 id="customer-addresses-title">Địa chỉ đã lưu</h2></div>
                        <span>{{ $user->addresses_count }}</span>
                    </div>
                    <div class="admin-customer-address-list">
                        @forelse ($user->addresses as $address)
                            <article>
                                <div>
                                    <strong>{{ $address->recipient_name }}</strong>
                                    @if ($address->is_default)<span>Địa chỉ mặc định</span>@endif
                                </div>
                                <p>{{ $address->phone }}</p>
                                <address>
                                    {{ $address->address_line_1 }}@if ($address->address_line_2), {{ $address->address_line_2 }}@endif,<br>
                                    {{ $address->ward }}, {{ $address->district }}, {{ $address->city }}
                                </address>
                            </article>
                        @empty
                            <p class="admin-empty">Khách hàng chưa lưu địa chỉ nào trong tài khoản.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>

        <form class="admin-promotion-form admin-customer-management-form" method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PATCH')

            <div class="admin-section-heading">
                <div><p class="admin-eyebrow">Quản lý tài khoản</p><h2>Cập nhật hồ sơ &amp; truy cập</h2></div>
                <p>Thay đổi tại đây ảnh hưởng trực tiếp đến thông tin đăng nhập và quyền truy cập của khách.</p>
            </div>

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
            @csrf
            @method('DELETE')
            <div>
                <strong>Xóa tài khoản</strong>
                <p>Thao tác này xóa thông tin đăng nhập và liên hệ theo cơ chế an toàn, nhưng giữ nguyên snapshot đơn hàng và lịch sử vận hành. Không thể xóa chính tài khoản đang đăng nhập hoặc tài khoản còn đơn/yêu cầu đang xử lý.</p>
                <label class="admin-delete-confirmation"><input name="delete_confirmation" type="checkbox" value="1" required> Tôi hiểu và muốn xóa tài khoản này.</label>
            </div>
            <button type="submit">Xóa tài khoản</button>
        </form>
    </section>
@endsection
