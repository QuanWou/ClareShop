@php
    $paymentMethod = \App\Modules\Orders\Support\PaymentMethodCatalog::get($order->payment_method);
    $latestPayment = $order->payments->sortByDesc('id')->first();
    $canResumePayOs = $order->status === 'pending' && $order->payment_method === 'bank_transfer' && $latestPayment?->provider === 'payos' && in_array($order->payment_status, ['unpaid', 'pending'], true);
    $canResumeOnlinePayment = $order->status === 'pending' && in_array($order->payment_method, ['paypal', 'momo'], true) && in_array($order->payment_status, ['unpaid', 'pending'], true);
    $canChoosePayment = $order->canCustomerChangePaymentMethod();
    $canCancelOrder = $order->canCustomerCancel();
    $firstPurchasableItem = $order->items->first(fn ($item) => filled($item->product_slug));
    $detailUrl = route('account.orders.show', $order);
@endphp

<article class="account-order-card">
    <header class="account-order-card-header">
        <div class="account-order-identity">
            <strong>Mã đơn: #{{ $order->number }}</strong>
            <time datetime="{{ $order->placed_at?->toIso8601String() }}">{{ $order->placed_at?->format('d/m/Y - H:i') }}</time>
        </div>
        <div class="account-order-statuses" aria-label="Trạng thái đơn hàng">
            <span class="account-order-payment account-order-payment-{{ $order->payment_status }}">{{ $order->paymentStatusLabel() }}</span>
            <span aria-hidden="true">|</span>
            <strong class="account-order-state account-order-state-{{ $order->status }}">{{ mb_strtoupper($order->statusLabel()) }}</strong>
        </div>
    </header>

    <div class="account-order-items">
        @foreach ($order->items as $item)
            @php($productUrl = $item->product_slug ? route('catalog.products.show', $item->product_slug) : null)
            @php($imageUrl = $item->imageUrl())
            <div class="account-order-item">
                @if ($productUrl)<a class="account-order-item-image" href="{{ $productUrl }}" aria-label="Xem {{ $item->product_name }}">@else<div class="account-order-item-image">@endif
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $item->product_name }}" width="80" height="80" loading="lazy">
                    @else
                        <span aria-hidden="true">CLARE</span>
                    @endif
                @if ($productUrl)</a>@else</div>@endif

                <div class="account-order-item-copy">
                    @if ($productUrl)
                        <a href="{{ $productUrl }}">{{ $item->product_name }}</a>
                    @else
                        <strong>{{ $item->product_name }}</strong>
                    @endif
                    <span>Phân loại: {{ $item->color_name }}</span>
                    <small>SKU {{ $item->sku }}</small>
                    <b>×{{ $item->quantity }}</b>
                </div>

                <div class="account-order-item-price">
                    <span>{{ \App\Modules\Shared\Support\Money::formatVnd($item->unit_price) }} / sản phẩm</span>
                    <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($item->line_total) }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <footer class="account-order-card-footer">
        <div class="account-order-meta">
            <p><span>Thanh toán</span><strong>{{ $paymentMethod['label'] }}</strong></p>
            <p><span>Vận chuyển</span><strong>{{ $order->shipping_provider ?: 'Đang cập nhật' }}</strong></p>
            @if ($order->estimatedDeliveryDate() && ! in_array($order->status, ['completed', 'cancelled'], true))
                <p><span>Nhận dự kiến</span><strong>{{ $order->estimatedDeliveryDate()->format('d/m/Y') }}</strong></p>
            @endif
        </div>

        <div class="account-order-summary">
            <dl>
                <div><dt>Tạm tính</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->subtotal) }}</dd></div>
                <div><dt>Phí vận chuyển</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->shipping_fee) }}</dd></div>
                @if ((float) $order->discount_total > 0)
                    <div class="account-order-discount"><dt>Voucher{{ $order->discount?->code ? ' · '.$order->discount->code : '' }}</dt><dd>-{{ \App\Modules\Shared\Support\Money::formatVnd($order->discount_total) }}</dd></div>
                @endif
                <div class="account-order-grand-total"><dt>Thành tiền</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</dd></div>
            </dl>
        </div>

        <div class="account-order-actions">
            @if ($order->status === 'completed' && $firstPurchasableItem)
                <a class="account-order-action account-order-action-secondary" href="{{ route('catalog.products.show', $firstPurchasableItem->product_slug) }}#product-reviews">Đánh giá</a>
            @endif
            @if (in_array($order->status, ['completed', 'cancelled'], true) && $firstPurchasableItem)
                <a class="account-order-action account-order-action-secondary" href="{{ route('catalog.products.show', $firstPurchasableItem->product_slug) }}">Mua lại</a>
            @endif
            @if (in_array($order->status, ['processing', 'shipped'], true))
                <a class="account-order-action account-order-action-secondary" href="mailto:hello@clare.local?subject={{ rawurlencode('Hỗ trợ đơn '.$order->number) }}">Liên hệ Clare</a>
            @endif
            @if ($canCancelOrder)
                <a class="account-order-action account-order-action-secondary" href="{{ $detailUrl }}#cancel-order">Hủy đơn</a>
            @endif
            <a class="account-order-action {{ $canResumePayOs || $canResumeOnlinePayment || $order->status === 'shipped' ? 'account-order-action-secondary' : 'account-order-action-primary' }}" href="{{ $detailUrl }}">Chi tiết đơn hàng</a>
            @if ($canChoosePayment)
                <a class="account-order-action account-order-action-primary" href="{{ $detailUrl }}#payment-options">Chọn cách thanh toán</a>
            @elseif ($canResumePayOs)
                <a class="account-order-action account-order-action-primary" href="{{ $detailUrl }}#payment-qr">Xem lại mã QR</a>
            @elseif ($canResumeOnlinePayment)
                <a class="account-order-action account-order-action-primary" href="{{ $detailUrl }}">Tiếp tục thanh toán</a>
            @elseif ($order->status === 'shipped')
                <a class="account-order-action account-order-action-primary" href="{{ $detailUrl }}">Theo dõi đơn hàng</a>
            @endif
        </div>
    </footer>
</article>
