@php
    /** @var \App\Modules\Orders\Models\Order $order */
    /** @var \App\Modules\Orders\Models\Payment $payment */
    $paymentSuccessKey = 'payment-success-'.$order->number.'-'.($payment->getKey() ?? 'latest');
@endphp

<dialog
    class="payment-success-modal"
    data-payment-success-modal
    data-payment-success-key="{{ $paymentSuccessKey }}"
    data-payment-success-force="{{ session('payment_success') || request()->query('payment') === 'success' ? 'true' : 'false' }}"
    aria-labelledby="payment-success-title-{{ $order->number }}"
>
    <div class="payment-success-modal-card">
        <div class="payment-success-animation" data-payment-success-animation data-animation-path="{{ asset('animations/wrx-success.json') }}" aria-hidden="true"></div>

        <p class="eyebrow">Đã xác nhận giao dịch</p>
        <h2 id="payment-success-title-{{ $order->number }}">Thanh toán thành công</h2>
        <p class="payment-success-modal-copy">
            Clare đã nhận được thanh toán cho đơn <strong>{{ $order->number }}</strong>.
            Clare sẽ xác nhận đơn, chuẩn bị sản phẩm và cập nhật từng mốc giao hàng trong tài khoản của bạn.
        </p>

        <div class="payment-success-modal-meta">
            <span>Tổng thanh toán</span>
            <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>
        </div>

        <div class="payment-success-modal-actions">
            <a class="button button-primary" href="{{ route('account.orders.show', $order) }}" data-payment-success-close>Xem đơn hàng</a>
            <button class="text-link" type="button" data-payment-success-close>Đóng</button>
        </div>
    </div>
</dialog>
