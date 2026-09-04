@extends('layouts.admin', ['title' => 'Đơn hàng'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-orders-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Vận hành bán hàng</p>
                <h1 id="admin-orders-title">Đơn hàng.</h1>
            </div>
            <p>Trạng thái đơn và thanh toán là hai luồng độc lập. Mọi thay đổi đều được ghi lại cùng người thực hiện.</p>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.orders.index') }}">
            <label>
                <span>Tìm kiếm</span>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn, tên, email, điện thoại">
            </label>
            <label>
                <span>Trạng thái đơn</span>
                <select name="status">
                    <option value="">Tất cả</option>
                    @foreach (['pending' => 'Chờ xử lý', 'confirmed' => 'Đã xác nhận', 'processing' => 'Đang chuẩn bị', 'shipped' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Thanh toán</span>
                <select name="payment_status">
                    <option value="">Tất cả</option>
                    @foreach (['unpaid' => 'Chưa thanh toán', 'pending' => 'Chờ đối soát', 'paid' => 'Đã thanh toán', 'refunded' => 'Đã hoàn tiền', 'failed' => 'Thanh toán thất bại', 'expired' => 'Đã hết hạn'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Lọc</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Đặt lúc</th>
                        <th>Đơn hàng</th>
                        <th>Thanh toán</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a class="admin-record-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td><strong>{{ $order->customer_name }}</strong><span>{{ $order->customer_phone }}</span></td>
                            <td>{{ $order->placed_at?->format('H:i · d/m/Y') }}</td>
                            <td><span class="admin-status admin-status-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                            <td><span class="admin-status admin-payment-{{ $order->payment_status }}">{{ $order->paymentStatusLabel() }}</span></td>
                            <td><strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Không tìm thấy đơn hàng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="admin-pagination">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection
