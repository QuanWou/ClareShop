<section class="payment-qr-card" id="payment-qr" aria-labelledby="payment-qr-title" data-qr-payment data-qr-expires-at="{{ $payment?->expires_at?->toIso8601String() }}">
    <div>
        <p class="eyebrow">QR ngân hàng qua payOS</p>
        <h2 id="payment-qr-title">Quét mã để thanh toán.</h2>
        <p>
            Thanh toán đúng <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($payOs['amount']) }}</strong>
            với nội dung <strong>{{ $payOs['transfer_content'] }}</strong>.
            Trạng thái được cập nhật tự động sau khi payOS xác nhận giao dịch.
        </p>

        @if ($payment?->expires_at)
            <p class="payment-qr-countdown" data-qr-countdown aria-live="polite">Mã QR còn hiệu lực trong <strong>03:00</strong>.</p>
            <p class="payment-qr-expired" data-qr-expired hidden>Mã QR payOS đã hết hạn và không thể tiếp tục sử dụng.</p>
            <form method="POST" action="{{ route('payments.payos.retry', [$order, $payment]) }}" class="payment-qr-retry" data-qr-retry-form hidden>
                @csrf
                <button class="button button-primary" type="submit">Tạo mã payOS mới (3 phút)</button>
            </form>
        @endif

        <dl>
            @if (filled($payOs['bank_id'] ?? null))<div><dt>Mã ngân hàng</dt><dd>{{ $payOs['bank_id'] }}</dd></div>@endif
            @if (filled($payOs['account_number'] ?? null))<div><dt>Số tài khoản</dt><dd>{{ $payOs['account_number'] }}</dd></div>@endif
            @if (filled($payOs['account_name'] ?? null))<div><dt>Chủ tài khoản</dt><dd>{{ $payOs['account_name'] }}</dd></div>@endif
            <div><dt>Số tiền</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($payOs['amount']) }}</dd></div>
            <div><dt>Nội dung</dt><dd>{{ $payOs['transfer_content'] }}</dd></div>
            @if ($payment?->expires_at)<div><dt>Hạn thanh toán</dt><dd>{{ $payment->expires_at->format('H:i, d/m/Y') }}</dd></div>@endif
        </dl>

        <a class="button button-secondary payment-payos-link" href="{{ $payOs['checkout_url'] }}" rel="noopener noreferrer">Mở trang thanh toán payOS</a>
    </div>

    <div class="payment-qr-image-wrap" data-qr-image-wrap>
        <canvas data-payos-qr data-qr-value="{{ $payOs['qr_code'] }}" role="img" aria-label="Mã QR payOS thanh toán đơn {{ $order->number }}"></canvas>
        <p data-payos-qr-error hidden>Không thể dựng ảnh QR. Vui lòng mở trang thanh toán payOS.</p>
    </div>
</section>
