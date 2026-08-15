@extends('layouts.storefront', [
    'title' => 'Đặt hàng thành công',
    'description' => 'Xác nhận đơn hàng '.$order->number.' tại Clare.',
])

@php
    $vietQr = $payment?->payload;
@endphp

@section('content')
    <section class="order-complete section" aria-labelledby="order-complete-title">
        <div class="shell order-complete-shell">
            <div class="order-complete-heading">
                <p class="eyebrow">Đơn hàng đã được ghi nhận</p>
                <h1 id="order-complete-title">Cảm ơn bạn.</h1>
                <p>Đơn <strong>{{ $order->number }}</strong> hiện ở trạng thái <strong>{{ mb_strtolower($order->statusLabel()) }}</strong>. Bạn có thể theo dõi mọi mốc xử lý trong tài khoản.</p>
            </div>

            @include('orders.partials.tracking', ['order' => $order])

            @if ($order->payment_method === 'bank_transfer' && $vietQr)
                <section class="payment-qr-card" aria-labelledby="payment-qr-title">
                    <div>
                        <p class="eyebrow">Chuyển khoản VietQR</p>
                        <h2 id="payment-qr-title">Quét mã để thanh toán.</h2>
                        <p>Chuyển đúng <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong> với nội dung <strong>{{ $vietQr['transfer_content'] }}</strong>. Đơn sẽ chờ đối soát trước khi được xác nhận thanh toán.</p>

                        <dl>
                            <div><dt>Ngân hàng</dt><dd>{{ $vietQr['bank_id'] }}</dd></div>
                            <div><dt>Số tài khoản</dt><dd>{{ $vietQr['account_number'] }}</dd></div>
                            <div><dt>Chủ tài khoản</dt><dd>{{ $vietQr['account_name'] }}</dd></div>
                            <div><dt>Nội dung</dt><dd>{{ $vietQr['transfer_content'] }}</dd></div>
                        </dl>
                    </div>
                    <img src="{{ $vietQr['qr_code_url'] }}" alt="Mã VietQR thanh toán đơn {{ $order->number }}" width="320" height="320">
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

            <section class="order-detail-card" aria-labelledby="order-detail-title">
                <div>
                    <p class="eyebrow">Chi tiết đơn</p>
                    <h2 id="order-detail-title">{{ $order->items->count() }} mẫu đèn đã chọn</h2>
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
