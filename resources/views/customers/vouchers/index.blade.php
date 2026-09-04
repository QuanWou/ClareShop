@extends('layouts.storefront', [
    'title' => 'Ví voucher của tôi',
    'description' => 'Quản lý các voucher bạn đã nhận tại Clare.',
])

@php
    $discountLabel = static function ($promotion): string {
        return $promotion->discount_type === 'percentage'
            ? rtrim(rtrim(number_format($promotion->discount_value, 2, '.', ''), '0'), '.').'%' 
            : \App\Modules\Shared\Support\Money::formatVnd($promotion->discount_value);
    };
    $filters = ['all' => 'Tất cả', 'available' => 'Có thể dùng', 'upcoming' => 'Chưa đến hạn', 'reserved' => 'Đang giữ', 'used' => 'Đã dùng', 'expired' => 'Hết hạn'];
@endphp

@section('content')
    <section class="voucher-page section" aria-labelledby="my-voucher-title">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('catalog.home') }}">Trang chủ</a><span aria-hidden="true">/</span><a href="{{ route('account.show') }}">Tài khoản</a><span aria-hidden="true">/</span><span aria-current="page">Ví voucher</span></nav>

            <header class="voucher-page-heading">
                <div><p class="eyebrow">Tài khoản Clare</p><h1 id="my-voucher-title">Ví voucher<br>của tôi.</h1></div>
                <p>Chọn “Dùng ngay” để đưa mã vào checkout. Điều kiện đơn hàng và mức giảm luôn được xác minh lại ở máy chủ.</p>
            </header>

            <nav class="voucher-filter-list" aria-label="Lọc voucher">
                @foreach ($filters as $value => $label)
                    <a href="{{ route('account.vouchers.index', ['filter' => $value]) }}" @class(['is-active' => $filter === $value])>{{ $label }}</a>
                @endforeach
            </nav>

            <div class="voucher-grid voucher-grid-account">
                @forelse ($voucherRows as $row)
                    @php($voucher = $row['voucher'])
                    @php($promotion = $voucher->promotionCode)
                    <article class="voucher-card voucher-card-{{ $row['state'] }}">
                        <div class="voucher-card-body">
                            <div class="voucher-card-topline"><p class="eyebrow">{{ $row['label'] }}</p><span>{{ $promotion?->code }}</span></div>
                            <h2>{{ $promotion?->name }}</h2>
                            <p>{{ $promotion?->description ?: 'Ưu đãi chỉ giảm tiền hàng, không giảm phí giao hàng.' }}</p>
                            <dl class="voucher-card-facts">
                                <div><dt>Ưu đãi</dt><dd>{{ $promotion ? $discountLabel($promotion) : '—' }}</dd></div>
                                <div><dt>Điều kiện</dt><dd>Từ {{ $promotion?->minimum_order_amount ? \App\Modules\Shared\Support\Money::formatVnd($promotion->minimum_order_amount) : 'mọi mức' }}</dd></div>
                                <div><dt>Thời gian</dt><dd>{{ $promotion?->starts_at?->format('d/m/Y') ?? 'Ngay' }} đến {{ $promotion?->ends_at?->format('d/m/Y') ?? 'không giới hạn' }}</dd></div>
                                <div><dt>Lượt cá nhân</dt><dd>{{ $voucher->used_count }}/{{ $promotion?->per_user_usage_limit ?? 0 }} lượt đã dùng</dd></div>
                            </dl>
                            <p class="voucher-card-message">{{ $row['message'] }}</p>
                            @if ($row['state'] === 'available')
                                <form method="POST" action="{{ route('account.vouchers.use', $voucher) }}">@csrf<button class="button button-primary" type="submit">Dùng ngay</button></form>
                            @else
                                <button class="button button-secondary" type="button" disabled>{{ $row['label'] }}</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="voucher-empty"><p class="eyebrow">Ví voucher</p><h2>Bạn chưa nhận voucher nào.</h2><p>Khám phá các ưu đãi Clare để lưu vào tài khoản trước khi checkout.</p><a class="button button-primary" href="{{ route('promotions.index') }}">Xem Ưu đãi & Voucher</a></div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
