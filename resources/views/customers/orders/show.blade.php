@extends('layouts.storefront', [
    'title' => 'Theo dõi đơn '.$order->number,
    'description' => 'Chi tiết và tiến trình đơn hàng '.$order->number.' tại Clare.',
])

@section('content')
    <section class="order-complete order-complete-page section" aria-labelledby="customer-order-title">
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

            @if ($errors->any())
                <div class="form-status form-status-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if (session('success'))
                <div class="form-status form-status-success" role="status">{{ session('success') }}</div>
            @endif

            @include('orders.partials.tracking', ['order' => $order])

            @if ($payment && in_array($payment->provider, ['momo', 'vnpay', 'payos', 'paypal'], true) && in_array($payment->status, ['unpaid', 'pending', 'expired'], true))
                <span class="payment-status-poll" data-payment-status-poll data-payment-status-url="{{ route('account.orders.payment-status', $order) }}" data-payment-success-url="{{ route('account.orders.show', ['order' => $order, 'payment' => 'success']) }}" data-payment-expires-at="{{ $payment->expires_at?->toIso8601String() }}" hidden></span>
            @endif

            @if ($payment?->status === 'paid' && in_array($payment->provider, ['momo', 'vnpay', 'payos', 'paypal'], true) && (session('payment_success') || request()->query('payment') === 'success'))
                @include('orders.partials.payment-success-modal', ['order' => $order, 'payment' => $payment])
            @endif

            @if ($payOs)
                @include('orders.partials.payos-payment', ['order' => $order, 'payment' => $payment, 'payOs' => $payOs])
            @elseif ($order->status === 'pending'
                && $order->payment_method === 'bank_transfer'
                && $payment?->provider === 'payos'
                && in_array($payment->status, ['unpaid', 'pending', 'failed', 'expired'], true)
                && ($payment->expires_at?->isPast() || in_array($payment->status, ['failed', 'expired'], true)))
                <section class="order-payment-note payment-qr-expired-card"><p class="eyebrow">payOS</p><h2>Phiên QR không còn hiệu lực.</h2><p>Phiên thanh toán cũ không thể tiếp tục sử dụng. Bạn có thể tạo mã payOS mới cho đúng đơn này.</p><form method="POST" action="{{ route('payments.payos.retry', [$order, $payment]) }}">@csrf<button class="button button-primary" type="submit">Tạo mã payOS mới (3 phút)</button></form></section>
            @endif

            @include('orders.partials.customer-payment-actions', [
                'order' => $order,
                'payment' => $payment,
                'paymentMethods' => $paymentMethods,
            ])

            <section class="order-detail-card" aria-labelledby="customer-order-detail-title">
                <div>
                    <p class="eyebrow">Chi tiết đơn</p>
                    <h2 id="customer-order-detail-title">{{ $order->items->count() }} mẫu đèn đã chọn</h2>
                </div>

                @include('orders.partials.order-items', ['order' => $order])

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

                @php($latestPayment = $order->payments->sortByDesc('id')->first())
                @if ($order->payment_method === 'paypal' && $latestPayment)
                    <div class="order-payment-note">
                        <p><strong>PayPal:</strong> {{ $order->paymentStatusLabel() }}</p>
                        @if ($latestPayment->gateway_amount !== null)
                            <p>{{ number_format((float) $latestPayment->gateway_amount, 2) }} {{ $latestPayment->gateway_currency }} · tỷ giá 1 {{ $latestPayment->gateway_currency }} = {{ number_format((float) $latestPayment->exchange_rate, 0, ',', '.') }} VND.</p>
                        @endif
                        @if ($latestPayment->provider_transaction_id)
                            <p>Mã giao dịch: {{ $latestPayment->provider_transaction_id }}</p>
                        @endif
                        @if ($order->status === 'pending' && in_array($latestPayment->status, ['pending', 'failed', 'expired'], true))
                            <form method="POST" action="{{ route('payments.paypal.retry', [$order, $latestPayment]) }}">
                                @csrf
                                <button class="button button-primary" type="submit">{{ $latestPayment->status === 'pending' ? 'Tiếp tục với PayPal' : 'Thử thanh toán lại' }}</button>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($order->payment_method === 'momo' && $latestPayment)
                    <div class="order-payment-note">
                        <p><strong>MoMo:</strong> {{ $order->paymentStatusLabel() }}</p>
                        @if ($latestPayment->provider_transaction_id)
                            <p>Mã giao dịch: {{ $latestPayment->provider_transaction_id }}</p>
                        @endif
                        @if ($order->status === 'pending' && in_array($latestPayment->status, ['unpaid', 'pending', 'failed', 'expired'], true))
                            <form method="POST" action="{{ route('payments.momo.retry', [$order, $latestPayment]) }}">
                                @csrf
                                <button class="button button-primary" type="submit">{{ $latestPayment->status === 'pending' ? 'Tiếp tục với MoMo' : 'Tạo phiên MoMo mới (3 phút)' }}</button>
                            </form>
                        @endif
                    </div>
                @endif

                <p class="order-shipping-note">Giao đến {{ $order->shipping_recipient_name }} · {{ $order->shipping_phone }}<br>{{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}.@if($order->shipping_fee_is_estimated)<br>Phí giao hiển thị là ước tính nội bộ của đơn vị vận chuyển đã chọn.@endif</p>
            </section>

            <a class="text-link order-account-back" href="{{ route('account.show') }}">Trở về tài khoản</a>
        </div>
    </section>
@endsection
