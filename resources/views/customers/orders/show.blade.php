@extends('layouts.storefront', [
    'title' => 'Theo dõi đơn '.$order->number,
    'description' => 'Chi tiết và tiến trình đơn hàng '.$order->number.' tại Clare.',
])

@section('content')
    <section class="order-complete section" aria-labelledby="customer-order-title">
        <div class="shell order-complete-shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('account.show') }}">Tài khoản</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $order->number }}</span>
            </nav>

            <div class="order-complete-heading">
                <div>
                    <p class="eyebrow">Đơn hàng của bạn</p>
                    <h1 id="customer-order-title">{{ $order->number }}</h1>
                </div>
                <p>Đặt lúc {{ $order->placed_at?->format('H:i, d/m/Y') }}. Thông tin dưới đây được cập nhật khi đội ngũ Clare xử lý đơn.</p>
            </div>

            @include('orders.partials.tracking', ['order' => $order])

            <section class="order-detail-card" aria-labelledby="customer-order-detail-title">
                <div>
                    <p class="eyebrow">Chi tiết đơn</p>
                    <h2 id="customer-order-detail-title">{{ $order->items->count() }} mẫu đèn đã chọn</h2>
                </div>

                <div class="order-complete-items">
                    @foreach ($order->items as $item)
                        <div>
                            <span>{{ $item->product_name }} · {{ $item->color_name }} × {{ $item->quantity }}</span>
                            <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($item->line_total) }}</strong>
                        </div>
                    @endforeach
                </div>

                <dl class="order-complete-totals">
                    <div><dt>Thanh toán</dt><dd>{{ $paymentMethod['label'] }} · {{ $order->paymentStatusLabel() }}</dd></div>
                    <div><dt>Vận chuyển</dt><dd>{{ $order->shipping_provider ?? 'Ước tính nội bộ' }} · {{ $order->shipping_service ?? 'Giao tiêu chuẩn' }}</dd></div>
                    <div><dt>Tạm tính</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->subtotal) }}</dd></div>
                    <div><dt>Phí giao hàng</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->shipping_fee) }}</dd></div>
                    @if ((int) $order->discount_total > 0)
                        <div class="order-discount-line"><dt>Ưu đãi @if($order->discount) <small>{{ $order->discount->code }}</small> @endif</dt><dd>-{{ \App\Modules\Shared\Support\Money::formatVnd($order->discount_total) }}</dd></div>
                    @endif
                    <div><dt>Tổng thanh toán</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</dd></div>
                </dl>

                <p class="order-shipping-note">Giao đến {{ $order->shipping_recipient_name }} · {{ $order->shipping_phone }}<br>{{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}.@if($order->shipping_fee_is_estimated)<br>Phí giao hiển thị là ước tính nội bộ của đơn vị vận chuyển đã chọn.@endif</p>
            </section>

            <a class="text-link order-account-back" href="{{ route('account.show') }}">Trở về tài khoản</a>
        </div>
    </section>
@endsection
