@extends('layouts.storefront', [
    'title' => 'Đã ghi nhận đơn hàng',
    'description' => 'Xác nhận đơn hàng '.$order->number.' tại Clare.',
])

@section('content')
    <section class="order-complete order-complete-page section" aria-labelledby="order-complete-title">
        <div class="shell order-complete-shell">
            <div class="order-complete-heading">
                <div>
                    <p class="eyebrow">Đơn hàng đã được ghi nhận</p>
                    <h1 id="order-complete-title">Cảm ơn bạn.</h1>
                </div>
                <p>Đơn <strong>{{ $order->number }}</strong> hiện ở trạng thái <strong>{{ mb_strtolower($order->statusLabel()) }}</strong>. Bạn có thể theo dõi mọi mốc xử lý trong tài khoản.</p>
            </div>

            @if ($errors->has('payment'))
                <div class="form-status form-status-error" role="alert">{{ $errors->first('payment') }}</div>
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

            @if ($order->payment_method === 'bank_transfer' && $payOs)
                @include('orders.partials.payos-payment', ['order' => $order, 'payment' => $payment, 'payOs' => $payOs])
            @elseif ($order->payment_method === 'bank_transfer' && $payment?->status === 'paid')
                <section class="order-payment-note" aria-labelledby="payos-paid-title">
                    <p class="eyebrow">payOS</p>
                    <h2 id="payos-paid-title">Thanh toán đã hoàn tất.</h2>
                    <p>payOS đã xác nhận khoản chuyển khoản. Clare sẽ tiếp tục xử lý đơn hàng.</p>
                    @if ($payment->provider_transaction_id)
                        <p>Mã giao dịch: <strong>{{ $payment->provider_transaction_id }}</strong></p>
                    @endif
                </section>
            @elseif ($order->status === 'pending'
                && $order->payment_method === 'bank_transfer'
                && $payment?->provider === 'payos'
                && in_array($payment->status, ['unpaid', 'pending', 'failed', 'expired'], true)
                && ($payment->expires_at?->isPast() || in_array($payment->status, ['failed', 'expired'], true)))
                <section class="order-payment-note payment-qr-expired-card"><p class="eyebrow">payOS</p><h2>Phiên QR không còn hiệu lực.</h2><p>Phiên thanh toán cũ không thể tiếp tục sử dụng. Bạn có thể tạo mã payOS mới cho đúng đơn này.</p><form method="POST" action="{{ route('payments.payos.retry', [$order, $payment]) }}">@csrf<button class="button button-primary" type="submit">Tạo mã payOS mới (3 phút)</button></form></section>
            @elseif ($order->payment_method === 'paypal')
                <section class="order-payment-note" aria-labelledby="paypal-payment-title">
                    <p class="eyebrow">PayPal</p>
                    <h2 id="paypal-payment-title">
                        {{ $payment?->status === 'paid' ? 'Thanh toán đã hoàn tất.' : 'Đơn đang chờ thanh toán PayPal.' }}
                    </h2>
                    <p>
                        Tổng đơn: <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>.
                        @if ($payment?->gateway_amount !== null)
                            PayPal thu <strong>{{ number_format((float) $payment->gateway_amount, 2) }} {{ $payment->gateway_currency }}</strong>
                            theo tỷ giá đã lưu <strong>1 {{ $payment->gateway_currency }} = {{ number_format((float) $payment->exchange_rate, 0, ',', '.') }} VND</strong>.
                        @endif
                    </p>

                    <dl>
                        <div><dt>Trạng thái</dt><dd>{{ $order->paymentStatusLabel() }}</dd></div>
                        @if ($payment?->provider_reference)<div><dt>Mã PayPal Order</dt><dd>{{ $payment->provider_reference }}</dd></div>@endif
                        @if ($payment?->provider_transaction_id)<div><dt>Mã giao dịch</dt><dd>{{ $payment->provider_transaction_id }}</dd></div>@endif
                        @if ($payment?->expires_at && $payment->status !== 'paid')<div><dt>Hạn thanh toán</dt><dd>{{ $payment->expires_at->format('H:i, d/m/Y') }}</dd></div>@endif
                    </dl>

                    @if ($order->status === 'pending' && $payment && in_array($payment->status, ['pending', 'failed', 'expired'], true))
                        <form method="POST" action="{{ route('payments.paypal.retry', [$order, $payment]) }}">
                            @csrf
                            <button class="button button-primary" type="submit">{{ $payment->status === 'pending' ? 'Tiếp tục với PayPal' : 'Thử thanh toán lại' }}</button>
                        </form>
                    @endif
                </section>
            @elseif ($order->payment_method === 'momo')
                <section class="order-payment-note" aria-labelledby="momo-payment-title">
                    <p class="eyebrow">MoMo</p>
                    <h2 id="momo-payment-title">{{ $payment?->status === 'paid' ? 'Thanh toán đã hoàn tất.' : ($payment?->status === 'expired' ? 'Phiên MoMo đã hết hạn.' : 'Đơn đang chờ thanh toán MoMo.') }}</h2>
                    <p>Tổng đơn: <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>. Chỉ trạng thái do MoMo xác nhận mới được Clare ghi nhận là đã thanh toán.</p>
                    @if ($payment?->provider_transaction_id)
                        <p>Mã giao dịch: <strong>{{ $payment->provider_transaction_id }}</strong></p>
                    @endif
                    @if ($order->status === 'pending' && $payment && in_array($payment->status, ['unpaid', 'pending', 'failed', 'expired'], true))
                        <form method="POST" action="{{ route('payments.momo.retry', [$order, $payment]) }}">
                            @csrf
                            <button class="button button-primary" type="submit">{{ $payment->status === 'pending' ? 'Tiếp tục với MoMo' : 'Tạo phiên MoMo mới (3 phút)' }}</button>
                        </form>
                    @endif
                </section>
            @else
                <section class="order-payment-note">
                    <p class="eyebrow">{{ $paymentMethod['label'] }}</p>
                    <h2>{{ $paymentMethod['confirmation_title'] }}</h2>
                    <p>{{ $paymentMethod['confirmation_description'] }} Tổng tiền hiện tại là <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>.</p>
                    @if ($paymentMethod['is_simulated'])
                        <p class="order-payment-pending">Trạng thái hiện tại: <strong>{{ $order->paymentStatusLabel() }}</strong>. Chưa có giao dịch tiền thật nào được tạo hoặc xác nhận.</p>
                    @endif
                </section>
            @endif

            @include('orders.partials.customer-payment-actions', [
                'order' => $order,
                'payment' => $payment,
                'paymentMethods' => $paymentMethods,
            ])

            <section class="order-detail-card" aria-labelledby="order-detail-title">
                <div>
                    <p class="eyebrow">Chi tiết đơn</p>
                    <h2 id="order-detail-title">{{ $order->items->count() }} mẫu đèn đã chọn</h2>
                </div>

                @include('orders.partials.order-items', ['order' => $order])

                <dl class="order-complete-totals">
                    <div><dt>Tạm tính</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->subtotal) }}</dd></div>
                    <div><dt>Phí giao hàng</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->shipping_fee) }}</dd></div>
                    @if ((int) $order->discount_total > 0)
                        <div class="order-discount-line"><dt>Ưu đãi @if($order->discount) <small>{{ $order->discount->code }}</small> @endif</dt><dd>-{{ \App\Modules\Shared\Support\Money::formatVnd($order->discount_total) }}</dd></div>
                    @endif
                    <div><dt>Tổng thanh toán</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</dd></div>
                </dl>

                <p class="order-shipping-note">
                    Giao đến {{ $order->shipping_recipient_name }} · {{ $order->shipping_phone }}<br>
                    {{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}.<br>
                    Đơn vị vận chuyển: <strong>{{ $order->shipping_provider ?? 'Ước tính nội bộ' }} · {{ $order->shipping_service ?? 'Giao tiêu chuẩn' }}</strong>@if($order->shipping_fee_is_estimated) <span>· phí giao là ước tính nội bộ</span>@endif
                </p>
            </section>

            <div class="order-complete-actions">
                <a class="button button-primary" href="{{ route('account.orders.show', $order) }}">Theo dõi đơn trong tài khoản</a>
                <a class="text-link" href="{{ route('catalog.products.index') }}">Tiếp tục mua sắm</a>
            </div>
        </div>
    </section>
@endsection
