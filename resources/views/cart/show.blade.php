@extends('layouts.storefront', [
    'title' => 'Giỏ hàng',
    'description' => 'Xem và cập nhật các mẫu đèn đã chọn trong giỏ hàng Clare.',
])

@section('content')
    <section class="cart-page section">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Giỏ hàng</span>
            </nav>

            <div class="cart-heading">
                <div>
                    <p class="eyebrow">Những mẫu đèn bạn đã chọn</p>
                    <h1>Giỏ hàng.</h1>
                </div>
                <p>{{ $cartLines->sum(fn ($line) => $line['item']->quantity) }} sản phẩm</p>
            </div>

            @if ($cartLines->isEmpty())
                <div class="empty-cart">
                    <p class="eyebrow">Một khoảng trống dịu dàng</p>
                    <h2>Giỏ hàng của bạn đang trống.</h2>
                    <p>Khám phá những dáng đèn mới và chọn một màu phù hợp với căn phòng.</p>
                    <a class="button button-primary" href="{{ route('catalog.home') }}#selected">Khám phá sản phẩm</a>
                </div>
            @else
                <div class="cart-layout">
                    <div class="cart-lines">
                        @foreach ($cartLines as $line)
                            <article class="cart-line">
                                @if ($line['is_available'])
                                    <a class="cart-line-image" href="{{ route('catalog.products.show', $line['product']) }}">
                                @else
                                    <div class="cart-line-image">
                                @endif
                                    @if ($line['image'])
                                        <img
                                            src="{{ $line['image']->url }}"
                                            alt="{{ $line['image']->alt_text ?? $line['product']?->name ?? 'Sản phẩm Clare' }}"
                                            width="360"
                                            height="360"
                                        >
                                    @else
                                        <span class="image-placeholder" aria-hidden="true"></span>
                                    @endif
                                @if ($line['is_available'])
                                    </a>
                                @else
                                    </div>
                                @endif

                                <div class="cart-line-copy">
                                    <div class="cart-line-title">
                                        <div>
                                            <p class="product-category">{{ $line['product']?->category?->name ?? 'Sản phẩm' }}</p>
                                            <h2>
                                                @if ($line['is_available'])
                                                    <a href="{{ route('catalog.products.show', $line['product']) }}">{{ $line['product']->name }}</a>
                                                @elseif ($line['product'])
                                                    {{ $line['product']->name }}
                                                @else
                                                    Sản phẩm không còn hiển thị
                                                @endif
                                            </h2>
                                            <p>{{ $line['variant']?->color_name }} · {{ $line['variant']?->sku }}</p>
                                        </div>
                                        <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($line['line_total']) }}</strong>
                                    </div>

                                    @if ($line['is_available'])
                                        <div class="cart-line-actions">
                                            <form method="POST" action="{{ route('cart.items.update', $line['item']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <label>
                                                    <span>Số lượng</span>
                                                    <input
                                                        type="number"
                                                        name="quantity"
                                                        value="{{ $line['item']->quantity }}"
                                                        min="1"
                                                        max="{{ $line['variant']->stock_quantity }}"
                                                        inputmode="numeric"
                                                    >
                                                </label>
                                                <button type="submit">Cập nhật</button>
                                            </form>

                                            <form method="POST" action="{{ route('cart.items.destroy', $line['item']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="remove-item-button" type="submit">Bỏ sản phẩm</button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="cart-line-unavailable">
                                            <p>Sản phẩm này hiện không còn bán hoặc đã hết hàng.</p>
                                            <form method="POST" action="{{ route('cart.items.destroy', $line['item']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="remove-item-button" type="submit">Bỏ sản phẩm</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="cart-summary" aria-labelledby="cart-summary-title">
                        <p class="eyebrow">Tóm tắt</p>
                        <h2 id="cart-summary-title">Đơn hàng của bạn</h2>

                        <dl>
                            <div>
                                <dt>Tạm tính</dt>
                                <dd>{{ \App\Modules\Shared\Support\Money::formatVnd($subtotal) }}</dd>
                            </div>
                            <div>
                                <dt>Tiền tệ</dt>
                                <dd>{{ $cart?->currency ?? config('commerce.currency') }}</dd>
                            </div>
                            <div>
                                <dt>Ưu đãi</dt>
                                <dd>Nhập mã tại checkout</dd>
                            </div>
                            <div>
                                <dt>Giao hàng</dt>
                                <dd>Tính theo địa chỉ và trọng lượng</dd>
                            </div>
                        </dl>

                        @guest
                            <p class="cart-summary-note">Bạn cần đăng nhập hoặc tạo tài khoản trước khi đặt đơn. Giỏ hàng sẽ được giữ lại để bạn tiếp tục sau khi đăng nhập.</p>
                        @else
                            <p class="cart-summary-note">Ở checkout, Clare sẽ tính phí giao hàng mô phỏng, kiểm tra mã ưu đãi, hiển thị ngày nhận dự kiến và cho bạn chọn COD hoặc chuyển khoản.</p>
                        @endguest
                        <a class="button button-primary button-wide" href="{{ route('checkout.show') }}">Đến checkout</a>
                        <a class="text-link cart-continue" href="{{ route('catalog.home') }}#selected">Tiếp tục mua sắm</a>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
