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
                <div class="cart-purchase-card">
                    <header class="cart-purchase-header">
                        <div>
                            <strong>CLARE · Giỏ hàng của bạn</strong>
                            <span>{{ $cartLines->count() }} mẫu đèn · {{ $cartLines->sum(fn ($line) => $line['item']->quantity) }} sản phẩm</span>
                        </div>
                        <div class="cart-purchase-header-actions">
                            <label class="cart-select-all"><input type="checkbox" @checked($cartLines->where('is_available', true)->isNotEmpty() && $cartLines->where('is_available', true)->every(fn ($line) => $line['item']->is_selected)) data-cart-select-all><span>Chọn tất cả</span></label>
                            <a href="{{ route('catalog.products.index') }}">Xem thêm sản phẩm</a>
                            <form class="cart-clear-form" method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Làm trống toàn bộ giỏ hàng?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Xóa toàn bộ</button>
                            </form>
                        </div>
                    </header>

                    <div class="cart-purchase-items">
                        @foreach ($cartLines as $line)
                            <article @class(['cart-purchase-item', 'is-unavailable' => ! $line['is_available'], 'is-unselected' => $line['is_available'] && ! $line['item']->is_selected]) data-cart-line data-line-total="{{ $line['line_total'] }}" data-line-quantity="{{ $line['item']->quantity }}">
                                <label class="cart-item-selector" aria-label="Chọn {{ $line['product']?->name ?? 'sản phẩm' }} để thanh toán">
                                    <input type="checkbox" name="cart_item_ids[]" value="{{ $line['item']->getKey() }}" form="cart-checkout-form" @checked($line['item']->is_selected && $line['is_available']) @disabled(! $line['is_available']) data-cart-item-selector>
                                    <span aria-hidden="true"></span>
                                </label>
                                @if ($line['is_available'])
                                    <a class="cart-purchase-item-image" href="{{ route('catalog.products.show', $line['product']) }}" aria-label="Xem {{ $line['product']->name }}">
                                @else
                                    <div class="cart-purchase-item-image">
                                @endif
                                    @if ($line['image'])
                                        <img
                                            src="{{ $line['image']->url }}"
                                            alt="{{ $line['image']->alt_text ?? $line['product']?->name ?? 'Sản phẩm Clare' }}"
                                            width="88"
                                            height="88"
                                            loading="lazy"
                                        >
                                    @else
                                        <span aria-hidden="true">CLARE</span>
                                    @endif
                                @if ($line['is_available'])
                                    </a>
                                @else
                                    </div>
                                @endif

                                <div class="cart-purchase-item-copy">
                                    <small>{{ $line['product']?->category?->name ?? 'Sản phẩm Clare' }}</small>
                                    @if ($line['is_available'])
                                        <a href="{{ route('catalog.products.show', $line['product']) }}">{{ $line['product']->name }}</a>
                                    @elseif ($line['product'])
                                        <strong>{{ $line['product']->name }}</strong>
                                    @else
                                        <strong>Sản phẩm không còn hiển thị</strong>
                                    @endif
                                    <span>Phân loại: {{ $line['variant']?->color_name ?: 'Đang cập nhật' }}</span>
                                    <span>SKU {{ $line['variant']?->sku ?: '—' }}</span>

                                    @if ($line['is_available'])
                                        <div class="cart-purchase-item-controls">
                                            <form class="cart-quantity-form" method="POST" action="{{ route('cart.items.update', $line['item']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <label aria-label="Số lượng {{ $line['product']->name }}">
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
                                                <button class="cart-remove-button" type="submit">Bỏ sản phẩm</button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="cart-purchase-item-unavailable">
                                            <p>Sản phẩm này hiện không còn bán hoặc đã hết hàng.</p>
                                            <form method="POST" action="{{ route('cart.items.destroy', $line['item']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="cart-remove-button" type="submit">Bỏ sản phẩm</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <div class="cart-purchase-item-price">
                                    @if ($line['is_available'])
                                        <span>{{ \App\Modules\Shared\Support\Money::formatVnd($line['unit_price']) }} / sản phẩm</span>
                                        <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($line['line_total']) }}</strong>
                                    @else
                                        <strong>Không khả dụng</strong>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <footer class="cart-purchase-footer" aria-labelledby="cart-summary-title">
                        <div class="cart-purchase-note">
                            <p class="eyebrow">Tóm tắt giỏ hàng</p>
                            @guest
                                <p>Bạn cần đăng nhập trước khi đặt đơn. Giỏ hàng vẫn được giữ lại sau khi đăng nhập.</p>
                            @else
                                <p>Phí vận chuyển và ngày nhận dự kiến được tính theo địa chỉ tại bước checkout.</p>
                            @endguest
                        </div>

                        <dl class="cart-purchase-totals">
                            <div>
                                <dt>Tạm tính (<span data-cart-selected-count>{{ $selectedQuantity }}</span> sản phẩm)</dt>
                                <dd data-cart-selected-subtotal>{{ \App\Modules\Shared\Support\Money::formatVnd($selectedSubtotal) }}</dd>
                            </div>
                            <div>
                                <dt>Voucher</dt>
                                <dd data-cart-voucher data-voucher-amount="{{ $cartVoucher->amount }}">
                                    @if ($cartVoucher->isApplied())
                                        -{{ \App\Modules\Shared\Support\Money::formatVnd($cartVoucher->amount) }} · {{ $cartVoucher->code }}
                                    @else
                                        {{ $cartVoucher->message ?: 'Chọn voucher tại checkout' }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>Phí vận chuyển</dt>
                                <dd>Tính tại checkout</dd>
                            </div>
                            <div class="cart-purchase-grand-total">
                                <dt>Thành tiền dự kiến</dt>
                                <dd data-cart-selected-total>{{ \App\Modules\Shared\Support\Money::formatVnd($selectedSubtotal - $cartVoucher->amount) }}</dd>
                            </div>
                        </dl>

                        <div class="cart-purchase-actions">
                            <a class="cart-purchase-secondary" href="{{ route('catalog.products.index') }}">Tiếp tục mua sắm</a>
                            <form id="cart-checkout-form" method="POST" action="{{ route('cart.checkout') }}">
                                @csrf
                                <button class="cart-purchase-primary" type="submit" data-cart-checkout-submit @disabled($selectedQuantity === 0)>Tiến hành thanh toán (<span data-cart-checkout-count>{{ $selectedQuantity }}</span>)</button>
                            </form>
                        </div>
                    </footer>
                </div>
            @endif
        </div>
    </section>
@endsection
