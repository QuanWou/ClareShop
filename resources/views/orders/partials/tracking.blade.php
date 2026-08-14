@php
    $timeline = $order->fulfillmentTimeline();
    $estimatedDeliveryDate = $order->estimatedDeliveryDate();
    $paymentStatusLabel = match ($order->payment_status) {
        'unpaid' => 'Chưa thanh toán',
        'pending' => 'Chờ đối soát',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
        default => $order->payment_status,
    };
@endphp

<section class="order-tracking-card" aria-labelledby="order-tracking-title">
    <div class="order-tracking-heading">
        <div>
            <p class="eyebrow">Theo dõi đơn hàng</p>
            <h2 id="order-tracking-title">{{ $order->statusLabel() }}</h2>
        </div>
        @if ($estimatedDeliveryDate && $order->status !== 'cancelled')
            <p>Giao dự kiến <strong>{{ $estimatedDeliveryDate->format('d/m/Y') }}</strong>@if($order->shipping_fee_is_estimated) <small>mô phỏng</small>@endif</p>
        @endif
    </div>

    <dl class="order-tracking-facts">
        <div><dt>Mã đơn</dt><dd>{{ $order->number }}</dd></div>
        <div><dt>Thanh toán</dt><dd>{{ $order->payment_method === 'cod' ? 'COD · ' : 'Chuyển khoản · ' }}{{ $paymentStatusLabel }}</dd></div>
        <div><dt>Vận chuyển</dt><dd>{{ $order->shipping_service ?? 'Giao tiêu chuẩn (ước tính)' }}</dd></div>
        @if ($order->shipping_tracking_number)
            <div><dt>Mã theo dõi</dt><dd>{{ $order->shipping_tracking_number }}</dd></div>
        @endif
    </dl>

    <ol class="order-status-timeline">
        @foreach ($timeline as $step)
            <li class="@if($step['complete']) is-complete @endif @if($step['current']) is-current @endif @if($step['status'] === 'cancelled') is-cancelled @endif">
                <span class="order-timeline-marker" aria-hidden="true"></span>
                <div>
                    <strong>{{ $step['label'] }}</strong>
                    <p>{{ $step['description'] }}</p>
                    @if ($step['at'])
                        <time datetime="{{ $step['at']->toDateTimeString() }}">{{ $step['at']->format('H:i · d/m/Y') }}</time>
                    @elseif ($step['current'])
                        <time>Đang chờ cập nhật</time>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @if ($order->shipping_fee_is_estimated && $order->status !== 'cancelled')
        <p class="order-tracking-disclaimer">Phí giao hàng và ngày nhận ở trên là ước tính nội bộ theo địa chỉ, trọng lượng đơn; chưa phải báo giá hoặc cam kết của đơn vị vận chuyển.</p>
    @endif
</section>
