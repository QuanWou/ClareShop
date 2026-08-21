@extends('layouts.storefront', [
    'title' => $product->seo_title ?: $product->name,
    'description' => $product->seo_description ?: $product->short_description,
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

                        @if ($displayImage)
                            <button class="product-gallery-zoom" type="button" aria-label="Phóng to ảnh {{ $product->name }}" data-gallery-lightbox-open>
                                <span aria-hidden="true">⌕</span> Phóng to
                            </button>
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
                        <div class="product-detail-meta">
                            @if ($product->brand)<span>{{ $product->brand->name }}</span>@endif
                            <a href="#product-reviews">
                                <span class="review-stars" aria-hidden="true">{{ str_repeat('★', (int) round((float) $product->approved_reviews_average)).str_repeat('☆', 5 - (int) round((float) $product->approved_reviews_average)) }}</span>
                                {{ $product->approved_reviews_count }} đánh giá
                            </a>
                            <span>{{ number_format((int) ($product->sold_count ?? 0), 0, ',', '.') }} đã bán</span>
                        </div>
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
                        <form class="product-buy-now-form" action="{{ route('buy-now') }}" method="POST" data-buy-now-form>
                            @csrf
                            <input type="hidden" name="product_variant_id" value="{{ $selectedVariant->getKey() }}" data-buy-now-variant>
                            <input type="hidden" name="quantity" value="1" data-buy-now-quantity>
                            <button class="button button-secondary button-wide" type="submit" @disabled(! $selectedVariant->isInStock()) data-buy-now-button>Mua ngay</button>
                        </form>
                        @auth
                            <form action="{{ route('wishlist.toggle', $product) }}" method="POST" class="product-detail-wishlist" data-wishlist-form>@csrf<button type="submit" aria-pressed="{{ $product->is_wishlisted ?? false ? 'true' : 'false' }}" data-wishlist-button><span aria-hidden="true">{{ $product->is_wishlisted ?? false ? '♥' : '♡' }}</span> {{ $product->is_wishlisted ?? false ? 'Đã lưu yêu thích' : 'Lưu vào yêu thích' }}</button></form>
                        @endauth
                    @else
                        <div class="product-unavailable">
                            <p>Sản phẩm hiện chưa có biến thể đang bán.</p>
                            <a href="{{ route('appointments.create') }}">Nhờ Clare báo khi có mẫu phù hợp</a>
                        </div>
                    @endif

                    <div class="product-assurances" aria-label="Thông tin mua hàng">
                        <p><span aria-hidden="true">01</span><strong>Giá rõ ràng</strong><small>Theo đúng màu bạn chọn</small></p>
                        <p><span aria-hidden="true">02</span><strong>Tồn kho thật</strong><small>Kiểm tra lại khi thêm giỏ</small></p>
                        <p><span aria-hidden="true">03</span><strong>Chưa chắc?</strong><a href="{{ route('appointments.create') }}">{{ $siteContent->get('product_consultation_label') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-story section" aria-labelledby="product-story-title">
        <div class="shell product-story-grid">
            <div class="product-story-heading" data-reveal>
                <p class="eyebrow">{{ $siteContent->get('product_story_eyebrow') }}</p>
                <h2 id="product-story-title">{{ $siteContent->get('product_story_title') }}</h2>
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
                    @foreach ($product->attributeValues->groupBy(fn ($value) => $value->attribute?->name) as $attributeName => $values)
                        @if ($attributeName)
                            <div>
                                <dt>{{ $attributeName }}</dt>
                                <dd>{{ $values->pluck('label')->join(', ') }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>

            @if ($secondaryEditorialImage)
                <figure class="product-story-image" data-reveal data-parallax="18">
                    <img src="{{ $secondaryEditorialImage->url }}" alt="{{ $secondaryEditorialImage->alt_text ?? 'Chi tiết '.$product->name }}" width="900" height="1100" loading="lazy">
                </figure>
            @endif
        </div>
    </section>

    <section class="product-reviews-section section" id="product-reviews" aria-labelledby="product-reviews-title">
        <div class="shell product-reviews-layout">
            <div class="product-review-summary" data-reveal>
                <p class="eyebrow">Trải nghiệm thật</p>
                <h2 id="product-reviews-title">Khách đã mua nói gì?</h2>
                <div class="product-rating-score">
                    <strong>{{ number_format((float) ($product->approved_reviews_average ?? 0), 1, ',', '.') }}</strong>
                    <div><span class="review-stars" aria-label="{{ number_format((float) ($product->approved_reviews_average ?? 0), 1, ',', '.') }} trên 5 sao">{{ str_repeat('★', (int) round((float) $product->approved_reviews_average)).str_repeat('☆', 5 - (int) round((float) $product->approved_reviews_average)) }}</span><small>{{ $product->approved_reviews_count }} đánh giá đã duyệt</small></div>
                </div>
                <div class="rating-distribution">
                    @foreach ([5, 4, 3, 2, 1] as $rating)
                        @php($ratingCount = (int) ($reviewDistribution[$rating] ?? 0))
                        <div><span>{{ $rating }} sao</span><i><b style="width: {{ $product->approved_reviews_count ? ($ratingCount / $product->approved_reviews_count) * 100 : 0 }}%"></b></i><small>{{ $ratingCount }}</small></div>
                    @endforeach
                </div>

                @auth
                    @if ($canReview)
                        <form class="product-review-form" action="{{ route('catalog.products.reviews.store', $product) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <h3>Viết đánh giá của bạn</h3>
                            <label><span>Số sao</span><select name="rating" required><option value="5">5 — Rất hài lòng</option><option value="4">4 — Hài lòng</option><option value="3">3 — Bình thường</option><option value="2">2 — Chưa hài lòng</option><option value="1">1 — Không hài lòng</option></select></label>
                            <label><span>Tiêu đề <small>Không bắt buộc</small></span><input name="title" value="{{ old('title') }}" maxlength="160"></label>
                            <label><span>Chia sẻ trải nghiệm</span><textarea name="comment" rows="5" minlength="10" maxlength="3000" required>{{ old('comment') }}</textarea></label>
                            <label><span>Ảnh thực tế <small>Tối đa 4 ảnh</small></span><input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label>
                            <button class="button button-primary" type="submit">Gửi đánh giá</button>
                        </form>
                    @elseif ($viewerReview)
                        <p class="review-pending-note">Đánh giá của bạn: <strong>{{ $viewerReview->statusLabel() }}</strong>.</p>
                    @endif
                @else
                    <p class="review-pending-note"><a href="{{ route('login') }}">Đăng nhập</a> để đánh giá sản phẩm đã mua.</p>
                @endauth
            </div>

            <div class="product-review-list" data-reveal-group>
                @forelse ($product->reviews as $review)
                    <article class="product-review-card" data-reveal-item>
                        <header><div><strong>{{ $review->user->name }}</strong><span class="review-stars" aria-label="{{ $review->rating }} trên 5 sao">{{ str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating) }}</span></div><time datetime="{{ $review->approved_at?->toDateString() }}">{{ $review->approved_at?->format('d/m/Y') }}</time></header>
                        @if ($review->is_verified_purchase)<p class="verified-purchase">✓ Đã mua hàng tại Clare</p>@endif
                        @if ($review->title)<h3>{{ $review->title }}</h3>@endif
                        <p>{{ $review->comment }}</p>
                        @if ($review->images->isNotEmpty())
                            <div class="review-image-grid">@foreach ($review->images as $image)<a href="{{ $image->url }}" target="_blank" rel="noopener"><img src="{{ $image->url }}" alt="Ảnh đánh giá của {{ $review->user->name }}" loading="lazy"></a>@endforeach</div>
                        @endif
                    </article>
                @empty
                    <div class="review-empty-state"><span aria-hidden="true">✦</span><h3>Chưa có đánh giá được hiển thị.</h3><p>Khách đã nhận hàng sẽ có thể chia sẻ trải nghiệm thực tế tại đây.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="related-products product-related-section section" aria-labelledby="related-products-title">
            <div class="shell">
                <div class="related-products-heading" data-reveal>
                    <div>
                        <p class="eyebrow">{{ $siteContent->get('product_related_eyebrow') }}</p>
                        <h2 id="related-products-title">{{ $siteContent->get('product_related_title') }}</h2>
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

    @if ($displayImage)
        <dialog class="product-gallery-lightbox" data-gallery-lightbox>
            <button type="button" aria-label="Đóng ảnh phóng to" data-gallery-lightbox-close>×</button>
            <img src="{{ $displayImage->url }}" alt="{{ $displayImage->alt_text ?? $product->name }}" data-gallery-lightbox-image>
            <p>Cuộn để xem toàn bộ ảnh · nhấn Esc để đóng</p>
        </dialog>
    @endif
@endsection
