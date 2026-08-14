@extends('layouts.storefront', [
    'title' => $product->name,
    'description' => $product->short_description,
    'bodyClass' => 'catalog-product-detail-page',
])

@php
    $primaryImage = $product->images->first();
    $selectedVariant = $product->activeVariants->first(fn ($variant) => $variant->isInStock())
        ?? $product->activeVariants->first();
    $displayImage = $selectedVariant?->images->first() ?? $primaryImage;
    $secondaryEditorialImage = $product->images->skip(1)->first();
@endphp

@section('content')
    <section class="product-showcase" aria-labelledby="product-title">
        <div class="shell">
            <nav class="catalog-breadcrumbs" aria-label="Đường dẫn trang" data-reveal data-reveal-immediate>
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                @if ($product->category)
                    <a href="{{ route('catalog.collections.show', $product->category) }}">{{ $product->category->name }}</a>
                    <span aria-hidden="true">/</span>
                @endif
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <div class="product-showcase-grid">
                <div class="product-gallery" data-product-gallery data-reveal data-reveal-immediate>
                    <div class="product-gallery-main" data-ambient>
                        @if ($displayImage)
                            <img
                                src="{{ $displayImage->url }}"
                                alt="{{ $displayImage->alt_text ?? $product->name }}"
                                width="1200"
                                height="1200"
                                fetchpriority="high"
                                data-gallery-main
                            >
                        @else
                            <span class="image-placeholder" aria-hidden="true"></span>
                        @endif

                        @if ($product->is_featured)
                            <span class="product-showcase-badge">Clare chọn</span>
                        @endif

                        <span class="product-gallery-hint" aria-hidden="true">Chạm ảnh nhỏ để đổi góc nhìn</span>
                    </div>

                    @if ($product->images->count() > 1)
                        <div class="product-gallery-thumbnails" aria-label="Chọn ảnh sản phẩm">
                            @foreach ($product->images as $image)
                                <button
                                    type="button"
                                    @class(['is-current' => $displayImage?->is($image)])
                                    aria-label="Xem ảnh {{ $loop->iteration }} của {{ $product->name }}"
                                    aria-pressed="{{ $displayImage?->is($image) ? 'true' : 'false' }}"
                                    data-gallery-thumbnail
                                    data-image-url="{{ $image->url }}"
                                    data-image-alt="{{ $image->alt_text ?? $product->name }}"
                                >
                                    <img src="{{ $image->url }}" alt="" width="220" height="220" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="product-purchase" data-product-options data-reveal data-reveal-immediate>
                    <div class="product-purchase-heading">
                        @if ($product->category)
                            <a class="product-category-link" href="{{ route('catalog.collections.show', $product->category) }}">{{ $product->category->name }}</a>
                        @endif
                        <h1 id="product-title">{{ $product->name }}</h1>
                        <p class="product-lede">{{ $product->short_description }}</p>
                    </div>

                    @if ($selectedVariant)
                        <div class="detail-price-row" aria-live="polite">
                            <strong data-current-price>{{ \App\Modules\Shared\Support\Money::formatVnd($selectedVariant->price) }}</strong>
                            <del data-compare-price @class(['is-hidden' => ! $selectedVariant->isDiscounted()])>
                                {{ $selectedVariant->isDiscounted() ? \App\Modules\Shared\Support\Money::formatVnd($selectedVariant->compare_at_price) : '' }}
                            </del>
                        </div>

                        <form
                            class="variant-form"
                            method="POST"
                            action="{{ route('cart.items.store') }}"
                            data-add-cart-form
                            data-product-image-selector="[data-gallery-main]"
                        >
                            @csrf

                            <fieldset>
                                <legend>Chọn màu <strong data-selected-color>{{ $selectedVariant->color_name }}</strong></legend>

                                <div class="variant-options">
                                    @foreach ($product->activeVariants as $variant)
                                        @php($variantImage = $variant->images->first() ?? $primaryImage)
                                        <label class="variant-option">
                                            <input
                                                type="radio"
                                                name="product_variant_id"
                                                value="{{ $variant->getKey() }}"
                                                @checked($variant->is($selectedVariant))
                                                data-variant-option
                                                data-color-name="{{ $variant->color_name }}"
                                                data-price="{{ \App\Modules\Shared\Support\Money::formatVnd($variant->price) }}"
                                                data-compare-price="{{ $variant->isDiscounted() ? \App\Modules\Shared\Support\Money::formatVnd($variant->compare_at_price) : '' }}"
                                                data-stock-label="{{ $variant->isInStock() ? 'Còn '.$variant->stock_quantity.' sản phẩm' : 'Tạm hết hàng' }}"
                                                data-in-stock="{{ $variant->isInStock() ? 'true' : 'false' }}"
                                                data-stock-quantity="{{ $variant->stock_quantity }}"
                                                data-image-url="{{ $variantImage?->url }}"
                                                data-image-alt="{{ $variantImage?->alt_text ?? $product->name.' màu '.$variant->color_name }}"
                                            >
                                            <span class="color-swatch" style="--swatch-color: {{ $variant->color_hex ?? '#ddd4c6' }}" aria-hidden="true"></span>
                                            <span>
                                                <strong>{{ $variant->color_name }}</strong>
                                                <small>{{ $variant->isInStock() ? 'Sẵn hàng' : 'Hết hàng' }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <p @class(['detail-stock', 'is-out-of-stock' => ! $selectedVariant->isInStock()]) data-stock-status aria-live="polite">
                                <span aria-hidden="true"></span>
                                {{ $selectedVariant->isInStock() ? 'Còn '.$selectedVariant->stock_quantity.' sản phẩm — có thể thêm vào giỏ ngay' : 'Tạm hết hàng' }}
                            </p>

                            <div class="purchase-actions">
                                <label class="quantity-field">
                                    <span>Số lượng</span>
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="1"
                                        min="1"
                                        max="{{ max(1, $selectedVariant->stock_quantity) }}"
                                        inputmode="numeric"
                                        data-quantity-input
                                    >
                                </label>

                                <button
                                    class="button button-primary button-wide"
                                    type="submit"
                                    data-add-cart-button
                                    @disabled(! $selectedVariant->isInStock())
                                >
                                    {{ $selectedVariant->isInStock() ? 'Thêm vào giỏ' : 'Tạm hết hàng' }}
                                </button>
                            </div>
                            <p class="add-cart-feedback" aria-live="polite" data-cart-feedback></p>
                        </form>
                    @else
                        <div class="product-unavailable">
                            <p>Sản phẩm hiện chưa có biến thể đang bán.</p>
                            <a href="{{ route('appointments.create') }}">Nhờ Clare báo khi có mẫu phù hợp</a>
                        </div>
                    @endif

                    <div class="product-assurances" aria-label="Thông tin mua hàng">
                        <p><span aria-hidden="true">01</span><strong>Giá rõ ràng</strong><small>Theo đúng màu bạn chọn</small></p>
                        <p><span aria-hidden="true">02</span><strong>Tồn kho thật</strong><small>Kiểm tra lại khi thêm giỏ</small></p>
                        <p><span aria-hidden="true">03</span><strong>Chưa chắc?</strong><a href="{{ route('appointments.create') }}">Nhờ Clare tư vấn</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-story section" aria-labelledby="product-story-title">
        <div class="shell product-story-grid">
            <div class="product-story-heading" data-reveal>
                <p class="eyebrow">Câu chuyện thiết kế</p>
                <h2 id="product-story-title">Ánh sáng ở đúng mức.</h2>
                <span aria-hidden="true">✦</span>
            </div>

            <div class="product-story-copy" data-reveal>
                <p>{{ $product->description }}</p>
                <dl>
                    @if ($product->material)
                        <div>
                            <dt>Chất liệu</dt>
                            <dd>{{ $product->material }}</dd>
                        </div>
                    @endif
                    @if ($product->dimensions)
                        <div>
                            <dt>Kích thước</dt>
                            <dd>{{ $product->dimensions }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Màu đang có</dt>
                        <dd>{{ $product->activeVariants->pluck('color_name')->join(', ') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($secondaryEditorialImage)
                <figure class="product-story-image" data-reveal data-parallax="18">
                    <img src="{{ $secondaryEditorialImage->url }}" alt="{{ $secondaryEditorialImage->alt_text ?? 'Chi tiết '.$product->name }}" width="900" height="1100" loading="lazy">
                </figure>
            @endif
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="related-products product-related-section section" aria-labelledby="related-products-title">
            <div class="shell">
                <div class="related-products-heading" data-reveal>
                    <div>
                        <p class="eyebrow">Bạn có thể cũng thích</p>
                        <h2 id="related-products-title">Cùng một bầu không khí.</h2>
                    </div>
                    @if ($product->category)
                        <a class="text-link" href="{{ route('catalog.collections.show', $product->category) }}">Xem cả bộ sưu tập <span aria-hidden="true">↗</span></a>
                    @endif
                </div>

                <div class="product-grid product-related-grid" data-reveal-group>
                    @foreach ($relatedProducts as $relatedProduct)
                        <x-product-card :product="$relatedProduct" reveal />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
