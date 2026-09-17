@extends('layouts.storefront', [
    'title' => 'Thanh toán khoản trả sau',
    'description' => 'Chủ động thanh toán khoản mua trước, trả sau tại Clare.',
])

@section('content')
    <section class="account-page section" aria-labelledby="pay-later-payment-title">
        <div class="shell account-shell">
            <div class="account-heading">
                <div><p class="eyebrow">Mua trước, trả sau</p><h1 id="pay-later-payment-title">Thanh toán khoản đến hạn.</h1></div>
                <p>Chỉ khi bạn chọn phương thức và bấm xác nhận, Clare mới xử lý thanh toán.</p>
            </div>

            @if ($errors->any())<div class="form-status form-status-error" role="alert">{{ $errors->first() }}</div>@endif
            @if (session('success'))<div class="form-status form-status-success" role="status">{{ session('success') }}</div>@endif

            <section class="account-panel">
                <dl class="order-complete-totals">
                    <div><dt>Mã đơn hàng</dt><dd>{{ $purchase->order->number }}</dd></div>
                    <div><dt>Tổng số tiền</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($purchase->total_amount) }}</dd></div>
                    <div><dt>Ngày đến hạn</dt><dd>{{ $purchase->due_at->format('d/m/Y') }}</dd></div>
                    <div><dt>Trạng thái</dt><dd>{{ $purchase->statusLabel() }}</dd></div>
                </dl>

                @if ($purchase->status === 'paid')
                    <p>Khoản trả sau đã được thanh toán đầy đủ lúc {{ $purchase->paid_at?->format('H:i, d/m/Y') }}.</p>
                @elseif ($purchase->canPayNow())
                    <form class="account-form" method="POST" action="{{ route('account.pay-later.pay.store', $purchase) }}">
                        @csrf
                        <fieldset>
                            <legend>Phương thức thanh toán</legend>
                            <label><input name="payment_method" type="radio" value="paypal" checked> <strong>PayPal</strong> · mô phỏng, không tạo giao dịch PayPal thật</label>
                            <label><input name="payment_method" type="radio" value="clare_pay"> <strong>Clare Pay</strong> · số dư {{ \App\Modules\Shared\Support\Money::formatVnd($wallet->balance) }}</label>
                            @if($payOsEnabled)<label><input name="payment_method" type="radio" value="payos"> <strong>PayOS</strong> · xác nhận thành công chỉ bằng webhook/API PayOS</label>@endif
                        </fieldset>
                        <label><input name="confirm_payment" type="checkbox" value="1" required> Tôi xác nhận thanh toán {{ \App\Modules\Shared\Support\Money::formatVnd($purchase->amount_due) }}.</label>
                        <button class="button button-primary" type="submit">Xác nhận thanh toán</button>
                    </form>
                    @if((float) $wallet->balance < (float) $purchase->amount_due)<p>Số dư Clare Pay còn thiếu {{ \App\Modules\Shared\Support\Money::formatVnd((float) $purchase->amount_due - (float) $wallet->balance) }}. <a href="{{ route('account.show') }}#clare-pay">Nạp tiền</a></p>@endif
                @elseif ($purchase->status === 'processing')
                    <p>Giao dịch đang chờ PayOS xác nhận. Clare không đánh dấu đã thanh toán chỉ dựa trên trang chuyển hướng.</p>
                @else
                    <p>Khoản này chưa đến hạn hoặc không còn cần thanh toán.</p>
                @endif
            </section>
            <a class="text-link" href="{{ route('account.show') }}#pay-later-purchases">Trở về tài khoản</a>
        </div>
    </section>
@endsection
