@extends('layouts.admin', ['title' => 'Tổng quan'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-dashboard-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Vận hành Clare</p>
                <h1 id="admin-dashboard-title">Tổng quan hôm nay.</h1>
            </div>
            <p>Đọc nhanh sức khỏe bán hàng, giao nhận, tồn kho và khách hàng trước khi đi vào từng nghiệp vụ.</p>
        </div>

        <div class="admin-metric-grid admin-metric-grid-wide" aria-label="Chỉ số vận hành">
            <article><span>Đơn chờ xác nhận</span><strong>{{ $metrics['pendingOrders'] }}</strong><a href="{{ route('admin.orders.index', ['status' => 'pending']) }}">Xử lý đơn</a></article>
            <article><span>Chờ đối soát</span><strong>{{ $metrics['pendingPayments'] }}</strong><a href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}">Xem thanh toán</a></article>
            <article><span>Đang giao</span><strong>{{ $metrics['activeDeliveries'] }}</strong><a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}">Theo dõi giao</a></article>
            <article><span>Tồn kho thấp</span><strong>{{ $metrics['lowStockVariants'] }}</strong><a href="{{ route('admin.catalog.products.index') }}">Kiểm tra catalog</a></article>
            <article><span>Khách hoạt động</span><strong>{{ $metrics['activeCustomers'] }}</strong><a href="{{ route('admin.users.index') }}">Quản lý tài khoản</a></article>
            <article><span>Yêu cầu mới</span><strong>{{ $metrics['pendingAppointments'] }}</strong><a href="{{ route('admin.appointments.index', ['status' => 'pending']) }}">Xem yêu cầu</a></article>
        </div>

        <div class="admin-value-strip admin-value-strip-wide">
            <div><span>Giá trị đơn chưa hủy</span><strong>{{ \App\Modules\Shared\Support\Money::formatVnd($metrics['activeOrderValue']) }}</strong></div>
            <div><span>Đã đối soát thanh toán</span><strong>{{ \App\Modules\Shared\Support\Money::formatVnd($metrics['paidRevenue']) }}</strong></div>
            <p>Chỉ số đối soát chỉ tính các đơn có payment đã ghi nhận là đã thanh toán, không phải doanh thu kế toán cuối kỳ.</p>
        </div>

        <div class="admin-analytics-grid">
            <section class="admin-panel" aria-labelledby="admin-status-chart-title">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Đơn hàng</p><h2 id="admin-status-chart-title">Phân bổ trạng thái</h2></div><span class="admin-chart-caption">{{ $totalOrders }} đơn</span></div>
                <div class="admin-donut-layout">
                    <div class="admin-donut" role="img" aria-label="Biểu đồ tròn trạng thái đơn hàng" style="background: {{ $statusDonut }}"><div><strong>{{ $totalOrders }}</strong><span>Tổng đơn</span></div></div>
                    <ul class="admin-chart-legend">
                        @foreach ($statusBreakdown as $status)
                            <li><span class="admin-chart-swatch" style="background: {{ $status['color'] }}"></span><span>{{ $status['label'] }}</span><b>{{ $status['count'] }}</b><small>{{ $status['percentage'] }}%</small></li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="admin-revenue-chart-title">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Bảy ngày gần nhất</p><h2 id="admin-revenue-chart-title">Giá trị đơn</h2></div><span class="admin-chart-caption">Không gồm đơn hủy</span></div>
                <div class="admin-bar-chart" role="img" aria-label="Biểu đồ cột giá trị đơn hàng trong bảy ngày gần nhất">
                    @foreach ($sevenDayRevenue as $day)
                        <div class="admin-bar-column"><span>{{ \App\Modules\Shared\Support\Money::formatVnd($day['amount']) }}</span><div><i style="height: {{ $day['height'] }}%"></i></div><b>{{ $day['label'] }}</b><small>{{ $day['orders'] }} đơn</small></div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="admin-dashboard-grid">
            <section class="admin-panel" aria-labelledby="low-stock-title">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Cần chú ý</p><h2 id="low-stock-title">Tồn kho thấp</h2></div><a href="{{ route('admin.catalog.products.index') }}">Quản lý sản phẩm</a></div>
                <div class="admin-compact-list">
                    @forelse ($lowStockVariants as $variant)
                        <a href="{{ route('admin.catalog.products.edit', $variant->product) }}"><span><strong>{{ $variant->product?->name ?? 'Sản phẩm đã lưu trữ' }}</strong><small>{{ $variant->color_name }} · {{ $variant->sku }}</small></span><span class="admin-stock-count">Còn {{ $variant->stock_quantity }}</span></a>
                    @empty
                        <p class="admin-empty">Không có biến thể đang bán nào ở mức tồn kho thấp.</p>
                    @endforelse
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="recent-orders-title">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Mới nhất</p><h2 id="recent-orders-title">Đơn hàng</h2></div><a href="{{ route('admin.orders.index') }}">Tất cả đơn</a></div>
                <div class="admin-compact-list">
                    @forelse ($recentOrders as $order)
                        <a href="{{ route('admin.orders.show', $order) }}"><span><strong>{{ $order->number }}</strong><small>{{ $order->customer_name }} · {{ $order->placed_at?->format('d/m H:i') }}</small></span><span class="admin-status admin-status-{{ $order->status }}">{{ $order->statusLabel() }}</span></a>
                    @empty
                        <p class="admin-empty">Chưa có đơn hàng nào.</p>
                    @endforelse
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="recent-appointments-title">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Mới nhất</p><h2 id="recent-appointments-title">Yêu cầu dịch vụ</h2></div><a href="{{ route('admin.appointments.index') }}">Tất cả yêu cầu</a></div>
                <div class="admin-compact-list">
                    @forelse ($recentAppointments as $appointment)
                        <a href="{{ route('admin.appointments.show', $appointment) }}"><span><strong>{{ $appointment->typeLabel() }}</strong><small>{{ $appointment->customer_name }} · {{ $appointment->preferred_starts_at->format('d/m H:i') }}</small></span><span class="admin-status admin-status-{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span></a>
                    @empty
                        <p class="admin-empty">Chưa có yêu cầu dịch vụ nào.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
