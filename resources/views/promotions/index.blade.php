@extends('layouts.storefront', [
    'title' => 'Ưu đãi & Voucher',
    'description' => 'Nhận voucher Clare vào tài khoản và dùng khi hoàn tất đơn hàng.',
])

@php
    $discountLabel = static function ($promotion): string {
        return $promotion->discount_type === 'percentage'
            ? rtrim(rtrim(number_format($promotion->discount_value, 2, '.', ''), '0'), '.').'%' 
            : \App\Modules\Shared\Support\Money::formatVnd($promotion->discount_value);
    };
@endphp

@section('content')
    <section class="voucher-page section" aria-labelledby="voucher-page-title">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a><span aria-hidden="true">/</span><span aria-current="page">Ưu đãi & Voucher</span>
            </nav>

            <header class="voucher-page-heading">
                <div>
                    <p class="eyebrow">Ưu đãi của Clare</p>
                    <h1 id="voucher-page-title">Một chút dịu dàng<br>cho đơn hàng của bạn.</h1>
                </div>
                <p>Nhận voucher vào tài khoản trước, rồi Clare sẽ kiểm tra điều kiện và tính lại mức giảm chính xác ở checkout.</p>
            </header>

            @auth
                <p class="voucher-page-account-copy">Voucher đã nhận được lưu trong <a href="{{ route('account.vouchers.index') }}">Ví voucher của tôi</a>.</p>
            @else
                <p class="voucher-page-account-copy">Đăng nhập để nhận voucher vào tài khoản và dùng cho đơn hàng của bạn.</p>
            @endauth

            <div class="voucher-grid">
                @forelse ($voucherRows as $row)
                    @php($promotion = $row['promotion'])
                    <article class="voucher-card voucher-card-{{ $row['state'] }}">
                        @if ($promotion->banner_path)
                            <img class="voucher-card-banner" src="{{ asset('storage/'.$promotion->banner_path) }}" alt="">
                        @endif
                        <div class="voucher-card-body">
                            <div class="voucher-card-topline">
                                <p class="eyebrow">{{ $row['label'] }}</p>
                                <span>{{ $promotion->code }}</span>
                            </div>
                            <h2>{{ $promotion->name }}</h2>
                            <p>{{ $promotion->description ?: 'Ưu đãi được áp dụng trên tổng tiền sản phẩm, không gồm phí giao hàng.' }}</p>

                            <dl class="voucher-card-facts">
                                <div><dt>Ưu đãi</dt><dd>{{ $discountLabel($promotion) }}</dd></div>
                                <div><dt>Đơn hàng</dt><dd>Từ {{ $promotion->minimum_order_amount ? \App\Modules\Shared\Support\Money::formatVnd($promotion->minimum_order_amount) : 'mọi mức' }}@if($promotion->maximum_order_amount) đến {{ \App\Modules\Shared\Support\Money::formatVnd($promotion->maximum_order_amount) }}@endif</dd></div>
                                @if ($promotion->maximum_discount_amount)<div><dt>Giảm tối đa</dt><dd>{{ \App\Modules\Shared\Support\Money::formatVnd($promotion->maximum_discount_amount) }}</dd></div>@endif
                                <div><dt>Thời gian dùng</dt><dd>{{ $promotion->starts_at?->format('d/m/Y H:i') ?? 'Ngay' }} đến {{ $promotion->ends_at?->format('d/m/Y H:i') ?? 'không giới hạn' }}</dd></div>
                                @if ($row['remaining_claims'] !== null)<div><dt>Còn lại</dt><dd>{{ $row['remaining_claims'] }} lượt nhận</dd></div>@endif
                            </dl>

                            <p class="voucher-card-message">{{ $row['message'] }}</p>

                            @if ($row['claimed'])
                                <a class="button button-secondary" href="{{ route('account.vouchers.index') }}">Xem trong Ví voucher</a>
                            @elseif ($row['claimable'])
                                <form method="POST" action="{{ route('promotions.claim', $promotion) }}">
                                    @csrf
                                    <button class="button button-primary" type="submit">Nhận voucher</button>
                                </form>
                            @else
                                <button class="button button-secondary" type="button" disabled>{{ $row['label'] }}</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="voucher-empty"><p class="eyebrow">Đang cập nhật</p><h2>Chưa có voucher công khai.</h2><p>Clare sẽ bổ sung những ưu đãi phù hợp vào đây.</p></div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
