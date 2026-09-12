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
    <section class="voucher-page voucher-page-public section" aria-labelledby="voucher-page-title">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a><span aria-hidden="true">/</span><span aria-current="page">Ưu đãi & Voucher</span>
            </nav>

            <header class="voucher-page-heading">
                <div>
                    <p class="eyebrow">Ưu đãi của Clare</p>
                    <h1 id="voucher-page-title">Voucher dành cho bạn.</h1>
                </div>
                <p>Lưu mã vào tài khoản và chọn lại ở checkout. Clare sẽ tự kiểm tra điều kiện và tính mức giảm chính xác.</p>
            </header>

            @auth
                <p class="voucher-page-account-copy">Voucher đã nhận được lưu trong <a href="{{ route('account.vouchers.index') }}">Ví voucher của tôi</a>.</p>
            @else
                <p class="voucher-page-account-copy">Đăng nhập để nhận voucher vào tài khoản và dùng cho đơn hàng của bạn.</p>
            @endauth

            <div class="voucher-grid voucher-grid-compact">
                @forelse ($voucherRows as $row)
                    @php($promotion = $row['promotion'])
                    <article class="voucher-card voucher-card-{{ $row['state'] }}">
                        <div class="voucher-card-stub" aria-hidden="true">
                            @if ($promotion->banner_path)
                                <img src="{{ asset('storage/'.$promotion->banner_path) }}" alt="">
                            @else
                                <strong>{{ $discountLabel($promotion) }}</strong>
                            @endif
                            <span>CLARE</span>
                        </div>
                        <div class="voucher-card-body">
                            <div class="voucher-card-topline">
                                <p class="eyebrow">{{ $row['label'] }}</p>
                                <span>{{ $promotion->code }}</span>
                            </div>
                            <h2>{{ $promotion->name }}</h2>
                            <p class="voucher-card-offer">Giảm <strong>{{ $discountLabel($promotion) }}</strong>@if($promotion->maximum_discount_amount) · tối đa {{ \App\Modules\Shared\Support\Money::formatVnd($promotion->maximum_discount_amount) }}@endif</p>
                            <p class="voucher-card-condition">Đơn từ {{ $promotion->minimum_order_amount ? \App\Modules\Shared\Support\Money::formatVnd($promotion->minimum_order_amount) : '0 VND' }} · Hạn {{ $promotion->ends_at?->format('d/m/Y') ?? 'không giới hạn' }}</p>
                            <div class="voucher-card-footer">
                                <span>{{ $row['message'] }}</span>
                                @if ($row['claimed'])
                                    <a class="voucher-card-action" href="{{ route('account.vouchers.index') }}">Đã lưu</a>
                                @elseif ($row['claimable'])
                                    <form method="POST" action="{{ route('promotions.claim', $promotion) }}">
                                        @csrf
                                        <button class="voucher-card-action" type="submit">Lưu mã</button>
                                    </form>
                                @else
                                    <button class="voucher-card-action" type="button" disabled>{{ $row['label'] }}</button>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="voucher-empty"><p class="eyebrow">Đang cập nhật</p><h2>Chưa có voucher công khai.</h2><p>Clare sẽ bổ sung những ưu đãi phù hợp vào đây.</p></div>
                @endforelse
            </div>

            @if ($recommendedProducts->isNotEmpty())
                <section class="voucher-products" aria-labelledby="voucher-products-title">
                    <div class="voucher-products-heading">
                        <div><p class="eyebrow">Dùng voucher ngay</p><h2 id="voucher-products-title">Có thể bạn sẽ thích</h2></div>
                        <a href="{{ route('catalog.products.index') }}">Xem tất cả sản phẩm</a>
                    </div>
                    <div class="voucher-product-rail">
                        @foreach ($recommendedProducts as $product)
                            <x-product-card :product="$product" cta-label="Mua sản phẩm" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
