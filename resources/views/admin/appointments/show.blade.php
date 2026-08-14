@extends('layouts.admin', ['title' => $appointment->number])

@section('content')
    <section class="admin-page admin-detail-page" aria-labelledby="admin-appointment-title">
        <a class="admin-back-link" href="{{ route('admin.appointments.index') }}">Trở về danh sách yêu cầu</a>

        <div class="admin-detail-heading">
            <div>
                <p class="admin-eyebrow">{{ $appointment->typeLabel() }}</p>
                <h1 id="admin-appointment-title">{{ $appointment->number }}</h1>
                <p>{{ $appointment->customer_name }} · gửi {{ $appointment->created_at->format('H:i, d/m/Y') }}</p>
            </div>
            <span class="admin-status admin-status-{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span>
        </div>

        <div class="admin-detail-grid">
            <div class="admin-detail-primary">
                <section class="admin-panel" aria-labelledby="admin-appointment-info-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Thông tin khách</p>
                            <h2 id="admin-appointment-info-title">Yêu cầu dịch vụ</h2>
                        </div>
                    </div>

                    <dl class="admin-information-list">
                        <div><dt>Loại yêu cầu</dt><dd>{{ $appointment->typeLabel() }}</dd></div>
                        <div><dt>Khách hàng</dt><dd>{{ $appointment->customer_name }} · {{ $appointment->customer_phone }} · {{ $appointment->customer_email }}</dd></div>
                        <div><dt>Thời gian mong muốn</dt><dd>{{ $appointment->preferred_starts_at->format('H:i, d/m/Y') }}@if($appointment->preferred_ends_at) đến {{ $appointment->preferred_ends_at->format('H:i, d/m/Y') }}@endif</dd></div>
                        @if ($appointment->scheduled_starts_at)
                            <div><dt>Lịch đã xác nhận</dt><dd>{{ $appointment->scheduled_starts_at->format('H:i, d/m/Y') }}@if($appointment->scheduled_ends_at) đến {{ $appointment->scheduled_ends_at->format('H:i, d/m/Y') }}@endif</dd></div>
                        @endif
                        @if ($appointment->order)
                            <div><dt>Đơn liên kết</dt><dd><a class="admin-record-link" href="{{ route('admin.orders.show', $appointment->order) }}">{{ $appointment->order->number }}</a></dd></div>
                        @endif
                        @if ($appointment->address_line_1)
                            <div><dt>Địa chỉ</dt><dd>{{ $appointment->address_line_1 }}@if($appointment->address_line_2), {{ $appointment->address_line_2 }}@endif, {{ $appointment->ward }}, {{ $appointment->district }}, {{ $appointment->city }}</dd></div>
                        @endif
                        @if ($appointment->customer_note)
                            <div><dt>Ghi chú khách</dt><dd>{{ $appointment->customer_note }}</dd></div>
                        @endif
                        @if ($appointment->internal_note)
                            <div><dt>Ghi chú nội bộ</dt><dd>{{ $appointment->internal_note }}</dd></div>
                        @endif
                    </dl>
                </section>

                <section class="admin-panel" aria-labelledby="admin-appointment-history-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Audit</p>
                            <h2 id="admin-appointment-history-title">Lịch sử xử lý</h2>
                        </div>
                    </div>

                    <ol class="admin-history-list">
                        @foreach ($appointment->statusHistories->sortByDesc('created_at') as $history)
                            <li>
                                <span>{{ $history->created_at->format('H:i · d/m/Y') }}</span>
                                <div>
                                    <strong>{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</strong>
                                    <p>{{ $history->note ?? 'Không có ghi chú.' }}</p>
                                    <small>{{ $history->changedBy?->name ?? 'Hệ thống' }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>

            <aside class="admin-detail-side">
                <section class="admin-action-card" aria-labelledby="admin-appointment-action-title">
                    <p class="admin-eyebrow">Điều phối dịch vụ</p>
                    <h2 id="admin-appointment-action-title">Cập nhật yêu cầu</h2>

                    @if ($nextStatuses)
                        <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <label>
                                <span>Trạng thái mới</span>
                                <select name="status" required>
                                    @foreach ($nextStatuses as $status)
                                        <option value="{{ $status }}">{{ match($status) {'confirmed' => 'Xác nhận lịch', 'completed' => 'Hoàn tất yêu cầu', 'cancelled' => 'Hủy yêu cầu'} }}</option>
                                    @endforeach
                                </select>
                            </label>

                            @if (in_array('confirmed', $nextStatuses, true))
                                <label>
                                    <span>Lịch đã xác nhận <small>Bắt buộc khi xác nhận</small></span>
                                    <input name="scheduled_starts_at" type="datetime-local" value="{{ old('scheduled_starts_at', $appointment->scheduled_starts_at?->format('Y-m-d\TH:i')) }}">
                                </label>
                                <label>
                                    <span>Kết thúc <small>Không bắt buộc</small></span>
                                    <input name="scheduled_ends_at" type="datetime-local" value="{{ old('scheduled_ends_at', $appointment->scheduled_ends_at?->format('Y-m-d\TH:i')) }}">
                                </label>
                                <label>
                                    <span>Liên kết đơn <small>Không bắt buộc</small></span>
                                    <input name="order_number" value="{{ old('order_number', $appointment->order?->number) }}" maxlength="32" placeholder="Ví dụ: CLR-260814-XXXXXXX">
                                </label>
                            @endif

                            <label>
                                <span>Ghi chú nội bộ <small>Bắt buộc khi hủy</small></span>
                                <textarea name="internal_note" rows="5" maxlength="2000">{{ old('internal_note', $appointment->internal_note) }}</textarea>
                            </label>
                            <button type="submit">Lưu thay đổi</button>
                        </form>
                    @else
                        <p class="admin-empty">Yêu cầu đã ở trạng thái cuối, không còn chuyển tiếp nào được phép.</p>
                    @endif
                </section>
            </aside>
        </div>
    </section>
@endsection
