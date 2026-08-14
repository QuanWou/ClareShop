@extends('layouts.storefront', [
    'title' => $category->name,
    'description' => $category->description,
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
            <div class="collection-filter-panel" data-reveal>
                <div>
                    <span>{{ $siteContent->get('collection_filter_label') }}</span>
                    <strong>{{ $category->name }}</strong>
                </div>

                <nav class="collection-filter" aria-label="Chuyển bộ sưu tập">
                    <a href="{{ route('catalog.products.index') }}">
                        <span>Tất cả</span>
                    </a>
                    @foreach ($categories as $filterCategory)
                        <a
                            @class(['is-current' => $category->is($filterCategory)])
                            href="{{ route('catalog.collections.show', $filterCategory) }}"
                            @if ($category->is($filterCategory)) aria-current="page" @endif
                        >
                            <span>{{ $filterCategory->name }}</span>
                            <small>{{ $filterCategory->published_products_count }}</small>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="collection-listing-heading" data-reveal>
                <div>
                    <p class="eyebrow">{{ $siteContent->get('collection_listing_eyebrow') }}</p>
                    <h2 id="collection-products-title">{{ $category->name }} đang có.</h2>
                </div>
                <p><strong>{{ $products->total() }}</strong> sản phẩm<br><span>Mẫu Clare chọn đặt trước</span></p>
            </div>

            <div class="product-grid collection-product-grid" data-reveal-group>
                @forelse ($products as $product)
                    <x-product-card :product="$product" reveal />
                @empty
                    <div class="catalog-empty-state">
                        <span aria-hidden="true">✦</span>
                        <h2>Bộ sưu tập đang được thắp sáng thêm.</h2>
                        <p>Trong lúc chờ mẫu mới, bạn có thể ghé xem toàn bộ những chiếc đèn đang có.</p>
                        <a class="button button-primary" href="{{ route('catalog.products.index') }}">Xem tất cả đèn</a>
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <div class="pagination-wrap collection-pagination">
                    <x-catalog-pagination :paginator="$products" />
                </div>
            @endif

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
