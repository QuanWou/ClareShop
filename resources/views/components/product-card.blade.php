@props([
    'product',
    'reveal' => false,
])

@php
    $primaryImage = $product->images->first();
    $secondaryImage = $product->images->skip(1)->first() ?? $primaryImage;
    $minimumPrice = (float) $product->minimum_price;
    $maximumPrice = (float) $product->maximum_price;
@endphp

<article class="product-card" @if ($reveal) data-reveal-item @endif>
    <a class="product-card-image" href="{{ route('catalog.products.show', $product) }}">
        @if ($primaryImage)
            <span class="product-card-media">
                <img
                    class="product-card-image-primary"
                    src="{{ $primaryImage->url }}"
                    alt="{{ $primaryImage->alt_text ?? $product->name }}"
                    loading="lazy"
                    width="900"
                    height="900"
                >

                @if ($secondaryImage)
                    <img
                        class="product-card-image-secondary"
                        src="{{ $secondaryImage->url }}"
                        alt=""
                        loading="lazy"
                        width="900"
                        height="900"
                    >
                @endif
            </span>
        @else
            <span class="image-placeholder" aria-hidden="true"></span>
        @endif

        @if ($product->is_featured)
            <span class="product-badge">Mẫu được chọn</span>
        @endif

    </a>

    @auth
        <form class="product-card-wishlist" action="{{ route('wishlist.toggle', $product) }}" method="POST" data-wishlist-form>
            @csrf
            <button type="submit" aria-label="{{ $product->is_wishlisted ?? false ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' }}" aria-pressed="{{ $product->is_wishlisted ?? false ? 'true' : 'false' }}" data-wishlist-button>
                <span aria-hidden="true">{{ $product->is_wishlisted ?? false ? '♥' : '♡' }}</span>
            </button>
        </form>
    @endauth

    <div class="product-card-copy">
        <div>
            <p class="product-category">{{ $product->category?->name }}</p>
            <h3><a href="{{ route('catalog.products.show', $product) }}">{{ $product->name }}</a></h3>
            @if (($product->approved_reviews_count ?? 0) > 0)<p class="product-card-rating"><span aria-hidden="true">★</span> {{ number_format((float) $product->approved_reviews_average, 1, ',', '.') }} · {{ $product->approved_reviews_count }} đánh giá</p>@endif
            <p class="product-card-description">{{ $product->short_description }}</p>
        </div>

        <div class="product-price-block">
            <p class="product-price">
                @if ($minimumPrice !== $maximumPrice)
                    Từ {{ \App\Modules\Shared\Support\Money::formatVnd($minimumPrice) }}
                @else
                    {{ \App\Modules\Shared\Support\Money::formatVnd($minimumPrice) }}
                @endif
            </p>

            @unless ($product->has_in_stock_variants)
                <span class="stock-note">Tạm hết hàng</span>
            @endunless
        </div>
    </div>

    <a class="product-card-cta" href="{{ route('catalog.products.show', $product) }}">Chọn màu và thêm giỏ</a>
</article>
