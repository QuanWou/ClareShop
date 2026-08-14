@extends('layouts.admin', ['title' => $order->number])

@php
    $paymentLabels = [
        'unpaid' => 'Chưa thanh toán',
        'pending' => 'Chờ đối soát',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];
@endphp

@section('content')
    <section class="admin-page admin-detail-page" aria-labelledby="admin-order-title">
        <a class="admin-back-link" href="{{ route('admin.orders.index') }}">Trở về danh sách đơn</a>

        <div class="admin-detail-heading">
            <div>
                <p class="admin-eyebrow">Đơn hàng</p>
                <h1 id="admin-order-title">{{ $order->number }}</h1>
                <p>{{ $order->customer_name }} · đặt {{ $order->placed_at?->format('H:i, d/m/Y') }}</p>
            </div>
            <div class="admin-detail-statuses">
                <span class="admin-status admin-status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                <span class="admin-status admin-payment-{{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] }}</span>
            </div>
        </div>

        <div class="admin-detail-grid">
            <div class="admin-detail-primary">
                <section class="admin-panel" aria-labelledby="admin-order-items-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Sản phẩm</p>
                            <h2 id="admin-order-items-title">Đơn đã chọn</h2>
                        </div>
                    </div>

                    <div class="admin-line-items">
                        @foreach ($order->items as $item)
                            <article>
                                <div>
                                    <strong>{{ $item->product_name }}</strong>
                                    <span>{{ $item->color_name }} · {{ $item->sku }} · {{ $item->quantity }} sản phẩm</span>
                                </div>
                                <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($item->line_total) }}</strong>
                            </article>
                        @endforeach
                    </div>

                    <dl class="admin-order-totals">
                        <div><dt>Tạm tính</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->subtotal) }}</dd></div>
                        <div><dt>Phí giao hàng @if($order->shipping_fee_is_estimated)<small>Ước tính</small>@endif</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->shipping_fee) }}</dd></div>
                        @if ((int) $order->discount_total > 0)
                            <div><dt>Ưu đãi <small>{{ $order->discount?->code ?? 'Đã áp dụng' }}</small></dt><dd>-{{ \App\Modules\Shared\Support\Money::formatVnd($order->discount_total) }}</dd></div>
                        @endif
                        <div><dt>Tổng đơn</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}</dd></div>
                    </dl>
                </section>

                <section class="admin-panel" aria-labelledby="admin-customer-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Giao hàng</p>
                            <h2 id="admin-customer-title">Khách &amp; địa chỉ</h2>
                        </div>
                    </div>

                    <dl class="admin-information-list">
                        <div><dt>Khách đặt</dt><dd>{{ $order->customer_name }}</dd></div>
                        <div><dt>Liên hệ</dt><dd>{{ $order->customer_phone }} · {{ $order->customer_email }}</dd></div>
                        <div><dt>Người nhận</dt><dd>{{ $order->shipping_recipient_name }} · {{ $order->shipping_phone }}</dd></div>
                        <div><dt>Địa chỉ</dt><dd>{{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}</dd></div>
                        <div><dt>Vận chuyển</dt><dd>{{ $order->shipping_provider ?? 'Ước tính nội bộ' }} · {{ $order->shipping_service ?? 'Giao tiêu chuẩn' }}@if($order->shipping_fee_is_estimated) · Ước tính nội bộ@endif</dd></div>
                        <div><dt>Giao dự kiến</dt><dd>{{ $order->estimatedDeliveryDate()?->format('d/m/Y') ?? 'Chưa có dự kiến' }}</dd></div>
                        <div><dt>Mã theo dõi</dt><dd>{{ $order->shipping_tracking_number ?? 'Sẽ sinh khi xác nhận đơn' }}</dd></div>
                        @if ($order->preparing_at || $order->shipped_at || $order->delivered_at)
                            <div><dt>Mốc giao hàng</dt><dd>@if($order->preparing_at)Chuẩn bị: {{ $order->preparing_at->format('H:i d/m/Y') }}<br>@endif @if($order->shipped_at)Bàn giao: {{ $order->shipped_at->format('H:i d/m/Y') }}<br>@endif @if($order->delivered_at)Đã giao: {{ $order->delivered_at->format('H:i d/m/Y') }}@endif</dd></div>
                        @endif
                        @if ($order->customer_note)
                            <div><dt>Ghi chú khách</dt><dd>{{ $order->customer_note }}</dd></div>
                        @endif
                    </dl>
                </section>

                <section class="admin-panel" aria-labelledby="admin-order-history-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Audit</p>
                            <h2 id="admin-order-history-title">Lịch sử đơn</h2>
                        </div>
                    </div>

                    <ol class="admin-history-list">
                        @foreach ($order->statusHistories->sortByDesc('created_at') as $history)
                            <li>
                                <span>{{ $history->created_at->format('H:i · d/m/Y') }}</span>
                                <div>
                                    <strong>{{ $history->from_status ? \App\Modules\Orders\Models\Order::statusLabelFor($history->from_status).' → ' : '' }}{{ \App\Modules\Orders\Models\Order::statusLabelFor($history->to_status) }}</strong>
                                    <p>{{ $history->note ?? 'Không có ghi chú.' }}</p>
                                    <small>{{ $history->changedBy?->name ?? 'Hệ thống' }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>

            <aside class="admin-detail-side">
                <section class="admin-action-card" aria-labelledby="admin-order-action-title">
                    <p class="admin-eyebrow">Vận hành đơn</p>
                    <h2 id="admin-order-action-title">Cập nhật trạng thái</h2>

                    @if ($nextStatuses)
                        <form method="POST" action="{{ route('admin.orders.status.update', $order) }}">
                            @csrf
                            @method('PATCH')
                            <label>
                                <span>Trạng thái mới</span>
                                <select name="status" required>
                                    @foreach ($nextStatuses as $status)
                                        <option value="{{ $status }}">{{ match($status) {'confirmed' => 'Xác nhận đơn → Chờ lấy hàng', 'processing' => 'Bắt đầu chuẩn bị giao', 'shipped' => 'Bàn giao → Đang giao hàng', 'completed' => 'Xác nhận đã giao', 'cancelled' => 'Hủy đơn'} }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span>Ghi chú nội bộ <small>Không bắt buộc</small></span>
                                <textarea name="admin_note" rows="4" maxlength="2000">{{ old('admin_note', $order->admin_note) }}</textarea>
                            </label>
                            @if (in_array('cancelled', $nextStatuses, true))
                                <label>
                                    <span>Lý do hủy <small>Bắt buộc khi hủy</small></span>
                                    <textarea name="cancel_reason" rows="3" maxlength="2000">{{ old('cancel_reason') }}</textarea>
                                </label>
                            @endif
                            <button type="submit">Lưu thay đổi</button>
                        </form>
                        <p class="admin-action-note">Mỗi cập nhật được ghi vào timeline khách hàng. Khi xác nhận đơn, hệ thống sinh mã theo dõi mô phỏng; hủy đơn hoàn tồn kho và trả lại lượt mã ưu đãi một lần. Đơn đã thanh toán cần ghi nhận hoàn tiền trước.</p>
                    @else
                        <p class="admin-empty">Đơn đã ở trạng thái cuối, không còn chuyển tiếp nào được phép.</p>
                    @endif
                </section>

                @foreach ($order->payments as $payment)
                    <section class="admin-action-card" aria-labelledby="payment-{{ $payment->id }}-title">
                        <p class="admin-eyebrow">{{ strtoupper($payment->provider) }}</p>
                        <h2 id="payment-{{ $payment->id }}-title">Thanh toán</h2>
                        <p class="admin-payment-amount">{{ \App\Modules\Shared\Support\Money::formatVnd($payment->amount) }}</p>
                        <p class="admin-action-note">Hiện tại: <strong>{{ $paymentLabels[$payment->status] }}</strong></p>

                        @if ($paymentNextStatuses[$payment->id]->isNotEmpty())
                            <form method="POST" action="{{ route('admin.orders.payment-status.update', [$order, $payment]) }}">
                                @csrf
                                @method('PATCH')
                                <label>
                                    <span>Ghi nhận</span>
                                    <select name="payment_status" required>
                                        @foreach ($paymentNextStatuses[$payment->id] as $status)
                                            <option value="{{ $status }}">{{ $status === 'paid' ? 'Đã thanh toán sau đối soát' : 'Đã hoàn tiền sau đối soát' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    <span>Ghi chú đối soát / mã giao dịch</span>
                                    <textarea name="payment_note" rows="3" maxlength="2000" required>{{ old('payment_note') }}</textarea>
                                </label>
                                <button type="submit">Ghi nhận thanh toán</button>
                            </form>
                        @endif

                        @if ($payment->statusHistories->isNotEmpty())
                            <ol class="admin-payment-history">
                                @foreach ($payment->statusHistories->sortByDesc('created_at') as $history)
                                    <li>{{ $history->created_at->format('d/m H:i') }} · {{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }} · {{ $history->changedBy?->name ?? 'Hệ thống' }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endforeach
            </aside>
        </div>
    </section>
@endsection
