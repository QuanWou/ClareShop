@extends('layouts.storefront', [
    'title' => $category->seo_title ?: $category->name,
    'description' => $category->seo_description ?: $category->description,
    'bodyClass' => 'catalog-collection-page',
])

@section('content')
    <section class="collection-hero" aria-labelledby="collection-title">
        <div class="shell">
            <nav class="catalog-breadcrumbs" aria-label="Đường dẫn trang" data-reveal data-reveal-immediate>
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog.products.index') }}">Tất cả đèn</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $category->name }}</span>
            </nav>

            <div class="collection-hero-grid">
                <div class="collection-hero-copy" data-reveal data-reveal-immediate>
                    <p class="eyebrow">Bộ sưu tập Clare / {{ str_pad($category->sort_order + 1, 2, '0', STR_PAD_LEFT) }}</p>
                    <h1 id="collection-title">{{ $category->name }} <em>{{ $siteContent->get('collection_heading_suffix') }}</em></h1>
                    <p class="collection-hero-intro">{{ $category->description }}</p>

                    <dl class="collection-hero-facts">
                        <div>
                            <dt>Số mẫu</dt>
                            <dd>{{ $products->total() }} thiết kế</dd>
                        </div>
                        <div>
                            <dt>Giá &amp; tồn kho</dt>
                            <dd>Theo từng màu</dd>
                        </div>
                        <div>
                            <dt>Cần giúp?</dt>
                            <dd><a href="{{ route('appointments.create') }}">Nhờ Clare tư vấn</a></dd>
                        </div>
                    </dl>
                </div>

                <figure class="collection-hero-image" data-reveal data-reveal-immediate data-ambient>
                    @if ($category->image_path)
                        <img src="{{ asset($category->image_path) }}" alt="Không gian với {{ mb_strtolower($category->name) }} Clare" width="960" height="960">
                    @else
                        <span class="image-placeholder" aria-hidden="true"></span>
                    @endif
                    <figcaption>
                        <span aria-hidden="true">✦</span>
                        Ánh sáng vừa đủ để căn phòng trở nên thân thuộc.
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <section class="collection-catalog section" aria-labelledby="collection-products-title">
        <div class="shell">
            <div class="catalog-mobile-toolbar" data-reveal>
                <button type="button" aria-controls="catalog-filter-sidebar" aria-expanded="false" data-catalog-filter-open>Bộ lọc</button>
                <span>{{ $products->total() }} sản phẩm phù hợp</span>
            </div>

            <div class="catalog-shop-layout">
                @include('catalog.partials.filters')

                <div class="catalog-results">
                    <div class="collection-listing-heading" data-reveal>
                        <div>
                            <p class="eyebrow">{{ $siteContent->get('collection_listing_eyebrow') }}</p>
                            <h2 id="collection-products-title">{{ $category->name }} đang có.</h2>
                        </div>
                        <div class="catalog-result-controls">
                            <label><span>Sắp xếp</span><select name="sort" form="catalog-filter-form" data-catalog-auto-submit><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Mới nhất</option><option value="bestselling" @selected(($filters['sort'] ?? '') === 'bestselling')>Bán chạy</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Giá tăng dần</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Giá giảm dần</option></select></label>
                            <div class="catalog-view-switch" aria-label="Kiểu hiển thị"><a @class(['is-current' => $viewMode === 'grid']) href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" aria-label="Hiển thị dạng lưới">▦</a><a @class(['is-current' => $viewMode === 'list']) href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" aria-label="Hiển thị dạng danh sách">☷</a></div>
                        </div>
                    </div>

                    <div @class(['product-grid', 'collection-product-grid', 'is-list-view' => $viewMode === 'list']) data-reveal-group>
                        @forelse ($products as $product)
                            <x-product-card :product="$product" reveal />
                        @empty
                            <div class="catalog-empty-state">
                                <span aria-hidden="true">✦</span>
                                <h2>Chưa có mẫu phù hợp với bộ lọc.</h2>
                                <p>Đặt lại bộ lọc hoặc xem toàn bộ những chiếc đèn đang có.</p>
                                <a class="button button-primary" href="{{ route('catalog.products.index') }}">Đặt lại bộ lọc</a>
                            </div>
                        @endforelse
                    </div>

                    @if ($products->hasPages())
                        <div class="pagination-wrap collection-pagination"><x-catalog-pagination :paginator="$products" /></div>
                    @endif
                </div>
            </div>

            <aside class="collection-guidance" aria-labelledby="collection-guidance-title" data-reveal data-ambient>
                <div class="collection-guidance-index" aria-hidden="true">01</div>
                <div>
                    <p class="eyebrow">Một gợi ý nhỏ</p>
                    <h2 id="collection-guidance-title">{{ $siteContent->get('collection_guidance_title') }}</h2>
                    <p>{{ $siteContent->get('collection_guidance_body') }}</p>
                </div>
                <a class="button button-light" href="{{ route('appointments.create') }}">{{ $siteContent->get('collection_guidance_cta') }}</a>
            </aside>
        </div>
    </section>
@endsection
