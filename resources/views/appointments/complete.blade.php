@extends('layouts.storefront', [
    'title' => 'Đã nhận yêu cầu dịch vụ',
    'description' => 'Xác nhận yêu cầu '.$appointment->number.' tại Clare.',
])

@section('content')
    <section class="appointment-complete section" aria-labelledby="appointment-complete-title">
        <div class="shell appointment-complete-shell">
            <div class="appointment-complete-heading">
                <p class="eyebrow">Yêu cầu đã được ghi nhận</p>
                <h1 id="appointment-complete-title">Cảm ơn bạn.</h1>
                <p>Mã yêu cầu <strong>{{ $appointment->number }}</strong> đang chờ Clare xem lại. Đây chưa phải lịch hẹn đã được xác nhận.</p>
            </div>

            <section class="appointment-confirmation-card" aria-labelledby="appointment-confirmation-title">
                <div>
                    <p class="eyebrow">Nhu cầu của bạn</p>
                    <h2 id="appointment-confirmation-title">
                        {{ $appointment->type === 'installation' ? 'Yêu cầu lắp đặt' : 'Tư vấn chọn đèn' }}
                    </h2>
                    <p>Thời gian bạn mong muốn: <strong>{{ $appointment->preferred_starts_at->format('H:i, d/m/Y') }}</strong>@if ($appointment->preferred_ends_at) đến <strong>{{ $appointment->preferred_ends_at->format('H:i, d/m/Y') }}</strong>@endif.</p>
                </div>

                <dl>
                    <div><dt>Trạng thái</dt><dd>Chờ xác nhận</dd></div>
                    <div><dt>Liên hệ</dt><dd>{{ $appointment->customer_name }} · {{ $appointment->customer_phone }}</dd></div>
                    @if ($appointment->address_line_1)
                        <div><dt>Địa chỉ</dt><dd>{{ $appointment->address_line_1 }}, {{ $appointment->ward }}, {{ $appointment->district }}, {{ $appointment->city }}</dd></div>
                    @endif
                </dl>
            </section>

            <p class="appointment-complete-note">Clare sẽ dùng thông tin bạn đã gửi để liên hệ và xác nhận các bước tiếp theo.</p>
            <a class="button button-primary" href="{{ route('catalog.products.index') }}">Khám phá các mẫu đèn</a>
        </div>
    </section>
@endsection
