<div class="order-line-items">
    @foreach ($order->items as $item)
        @php($productUrl = $item->product_slug ? route('catalog.products.show', $item->product_slug) : null)
        @php($imageUrl = $item->imageUrl())

        <article class="order-line-item">
            @if ($productUrl)
                <a class="order-line-item-image" href="{{ $productUrl }}" aria-label="Xem {{ $item->product_name }}">
            @else
                <div class="order-line-item-image">
            @endif
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $item->product_name }}" width="88" height="88" loading="lazy">
                @else
                    <span aria-hidden="true">CLARE</span>
                @endif
            @if ($productUrl)
                </a>
            @else
                </div>
            @endif

            <div class="order-line-item-copy">
                @if ($productUrl)
                    <a href="{{ $productUrl }}">{{ $item->product_name }}</a>
                @else
                    <strong>{{ $item->product_name }}</strong>
                @endif
                <span>{{ $item->color_name }}</span>
                <small>SKU {{ $item->sku }}</small>
            </div>

            <div class="order-line-item-quantity">
                <span>Số lượng</span>
                <strong>×{{ $item->quantity }}</strong>
            </div>

            <div class="order-line-item-price">
                <span>{{ \App\Modules\Shared\Support\Money::formatVnd($item->unit_price) }} / sản phẩm</span>
                <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($item->line_total) }}</strong>
            </div>
        </article>
    @endforeach
</div>
