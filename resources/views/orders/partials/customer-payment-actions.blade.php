@php
    $canChangePaymentMethod = $order->canCustomerChangePaymentMethod();
    $canCancelOrder = $order->canCustomerCancel();
    $hasActiveExternalPayment = $order->status === 'pending'
        && $order->payment_status === 'pending'
        && in_array($payment?->provider, ['paypal', 'momo', 'payos'], true)
        && ($payment?->expires_at?->isFuture() ?? false);
@endphp

@if ($canChangePaymentMethod || $canCancelOrder)
    <section class="order-payment-management" id="payment-options" aria-labelledby="payment-options-title">
        <div class="order-payment-management-heading">
            <div>
                <p class="eyebrow">Xử lý đơn chưa thanh toán</p>
                <h2 id="payment-options-title">Chọn cách tiếp tục</h2>
            </div>
            <p>Đơn vẫn được Clare giữ ở trạng thái chờ xác nhận. Bạn có thể đổi cách thanh toán hoặc hủy đơn.</p>
        </div>

        @if ($errors->has('payment_method'))
            <div class="form-status form-status-error" role="alert">{{ $errors->first('payment_method') }}</div>
        @endif

        @if ($canChangePaymentMethod)
            <form class="order-payment-change-form" method="POST" action="{{ route('account.orders.payment-method.update', $order) }}">
                @csrf
                @method('PATCH')

                <fieldset>
                    <legend>Phương thức thanh toán mới</legend>
                    <div class="order-payment-method-grid">
                        @foreach ($paymentMethods as $code => $method)
                            <label class="order-payment-method-option">
                                <input
                                    name="payment_method"
                                    type="radio"
                                    value="{{ $code }}"
                                    @checked(old('payment_method', $order->payment_method) === $code)
                                >
                                <span>
                                    <strong>{{ $method['label'] }}</strong>
                                    <small>{{ $method['description'] }}</small>
                                    @if ($code === $order->payment_method)
                                        <b>Phương thức hiện tại · tạo phiên mới</b>
                                    @elseif ($method['is_simulated'])
                                        <b>Đang mô phỏng, chưa tạo giao dịch tiền thật</b>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="order-payment-management-submit">
                    <p>Tổng tiền đơn hàng không thay đổi: <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</strong>.</p>
                    <button class="button button-primary" type="submit">Xác nhận phương thức mới</button>
                </div>
            </form>
        @endif

        @if ($canCancelOrder)
            <details class="order-cancel-panel" id="cancel-order" @if($errors->has('cancel_reason') || $errors->has('cancel_note') || $errors->has('confirm_cancel')) open @endif>
                <summary>Không tiếp tục mua — hủy đơn hàng</summary>
                <form method="POST" action="{{ route('account.orders.cancel', $order) }}">
                    @csrf
                    <label>
                        <span>Lý do hủy đơn</span>
                        <select name="cancel_reason" required>
                            <option value="">Chọn lý do</option>
                            @foreach (['Tôi gặp vấn đề khi thanh toán', 'Tôi muốn thay đổi sản phẩm hoặc số lượng', 'Thời gian giao hàng không phù hợp', 'Tôi không còn nhu cầu mua hàng', 'Lý do khác'] as $reason)
                                <option value="{{ $reason }}" @selected(old('cancel_reason') === $reason)>{{ $reason }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Thông tin bổ sung <small>Không bắt buộc, trừ khi chọn “Lý do khác”</small></span>
                        <textarea name="cancel_note" rows="3" maxlength="500">{{ old('cancel_note') }}</textarea>
                    </label>
                    <label class="order-cancel-confirmation">
                        <input name="confirm_cancel" type="checkbox" value="1" required @checked(old('confirm_cancel'))>
                        <span>Tôi hiểu đơn sẽ bị hủy, tồn kho và voucher sẽ được hoàn lại; thao tác này không thể khôi phục.</span>
                    </label>
                    @if ($errors->has('cancel_reason') || $errors->has('cancel_note') || $errors->has('confirm_cancel'))
                        <div class="form-status form-status-error" role="alert">
                            {{ $errors->first('cancel_reason') ?: ($errors->first('cancel_note') ?: $errors->first('confirm_cancel')) }}
                        </div>
                    @endif
                    <button class="button order-cancel-button" type="submit">Xác nhận hủy đơn</button>
                </form>
            </details>
        @endif
    </section>
@elseif ($hasActiveExternalPayment)
    <section class="order-payment-management order-payment-management-active" aria-label="Phiên thanh toán đang hoạt động">
        <p class="eyebrow">Phiên thanh toán đang hoạt động</p>
        <h2>Bạn vẫn có thể tiếp tục thanh toán.</h2>
        <p>Để đổi phương thức hoặc hủy đơn, hãy hủy giao dịch tại cổng thanh toán hoặc chờ phiên hiện tại hết hạn.</p>
    </section>
@endif
