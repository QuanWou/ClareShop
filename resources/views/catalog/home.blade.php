@extends('layouts.storefront', [
    'description' => $siteContent->get('home_meta_description'),
    'bodyClass' => 'catalog-home clare-cinematic',
])

@section('content')
    <section class="cinematic-hero" data-cinematic-home data-cinematic-hero aria-labelledby="cinematic-hero-title">
        <div class="shell cinematic-hero-stage">
            <div class="cinematic-hero-copy">
                <p class="eyebrow">{{ $siteContent->get('home_hero_eyebrow') }}</p>
                <h1 id="cinematic-hero-title">{{ $siteContent->get('home_hero_title_first') }}<br>{{ $siteContent->get('home_hero_title_second') }} <em>{{ $siteContent->get('home_hero_title_emphasis') }}</em></h1>
                <p class="cinematic-hero-intro">{{ $siteContent->get('home_hero_intro') }}</p>

                <div class="hero-actions">
                    <a class="button button-primary" href="#selected">{{ $siteContent->get('home_hero_primary_cta') }}</a>
                    <a class="text-link" href="#services">{{ $siteContent->get('home_hero_secondary_cta') }}</a>
                </div>
            </div>

            <div class="cinematic-perspective">
              <div class="cinematic-composition" data-depth-composition>
                <span class="cinematic-orbit" aria-hidden="true"></span>
                <figure class="cinematic-main-layer" data-depth-main>
                <div class="hero-visual-media cinematic-lamp-media" data-lamp-scene>
                    <img
                        src="{{ $siteContent->asset('home_hero_image') }}"
                        alt="{{ $siteContent->get('home_hero_image_alt') }}"
                        width="1536"
                        height="1024"
                        fetchpriority="high"
                    >
                    <span class="hero-lamp-light" aria-hidden="true"></span>
                    <button
                        class="hero-pull-cord"
                        type="button"
                        aria-pressed="false"
                        aria-label="Kéo dây để bật đèn"
                        aria-describedby="hero-lamp-instruction"
                        data-lamp-toggle
                    >
                        <span class="hero-pull-cord-handle" aria-hidden="true"></span>
                    </button>
                    <p class="hero-lamp-instruction" id="hero-lamp-instruction" data-lamp-instruction>Kéo dây để bật đèn</p>
                    <span class="sr-only" aria-live="polite" data-lamp-status>Đèn đang tắt.</span>
                </div>
                <figcaption><span>{{ $siteContent->get('home_hero_note') }}</span><span aria-hidden="true">01 / CLARE</span></figcaption>
                </figure>
                <figure class="cinematic-detail-layer" data-depth-layer="detail">
                    <img src="{{ $siteContent->asset('home_story_image') }}" alt="{{ $siteContent->get('home_story_image_alt') }}" width="320" height="400" loading="lazy">
                    <figcaption>Gần hơn một chút.<span>Chi tiết làm nên cảm giác.</span></figcaption>
                </figure>
                @if ($featuredProducts->isNotEmpty())
                    @php
                        $sceneProduct = $featuredProducts->first();
                    @endphp
                    <a class="cinematic-product-layer" data-depth-layer="product" href="{{ route('catalog.products.show', $sceneProduct) }}" data-motion-product="{{ $sceneProduct->slug }}">
                        @if ($sceneProduct->images->first())
                            <img src="{{ $sceneProduct->images->first()->url }}" alt="{{ $sceneProduct->name }}" width="220" height="220" loading="lazy" data-motion-image>
                        @endif
                        <span><small>{{ $sceneProduct->category?->name }}</small><strong>{{ $sceneProduct->name }}</strong><span>Khám phá <span aria-hidden="true">↗</span></span></span>
                    </a>
                @endif
                <div class="cinematic-note-layer" data-depth-layer="note"><span aria-hidden="true">✳</span><p>Một điểm sáng.<br>Một khoảng riêng.</p></div>
              </div>
            </div>
            <div class="cinematic-hero-bottom">
                <a href="#collections">Cuộn để khám phá <span aria-hidden="true">↓</span></a>
                <button type="button" data-motion-toggle aria-pressed="false" hidden>Giảm chuyển động</button>
                <span>Ánh sáng / Chất liệu / Không gian</span>
            </div>
        </div>
    </section>

    <section class="home-manifesto" aria-labelledby="home-manifesto-title" data-reveal>
        <div class="shell home-manifesto-grid">
            <p class="eyebrow">{{ $siteContent->get('home_manifesto_eyebrow') }}</p>
            <div>
                <h2 id="home-manifesto-title">{{ $siteContent->get('home_manifesto_title') }}</h2>
                <p>{{ $siteContent->get('home_manifesto_body') }}</p>
            </div>
        </div>
    </section>

    <section class="collections section" id="collections">
        <div class="shell">
            <div class="section-heading split-heading collection-section-heading" data-reveal>
                <div>
                    <p class="eyebrow">{{ $siteContent->get('home_collections_eyebrow') }}</p>
                    <h2>{{ $siteContent->get('home_collections_title') }}<br><em>{{ $siteContent->get('home_collections_emphasis') }}</em></h2>
                </div>
                <p>{{ $siteContent->get('home_collections_description') }}</p>
            </div>

            @php
                $collectionImagePool = collect($collectionImagePool ?? []);
            @endphp

            <div class="collection-grid" data-reveal-group>
                @forelse ($categories as $category)
                    @php
                        $collectionImages = $collectionImagePool;

                        if ($category->image_path) {
                            $collectionImages = $collectionImages->prepend($category->image_path);
                        }

                        $collectionImages = $collectionImages->filter()->unique()->values();
                        $rotationOffset = $collectionImages->count() > 1
                            ? ($loop->index * max(1, (int) floor($collectionImages->count() / 6))) % $collectionImages->count()
                            : 0;
                        $collectionImages = $collectionImages->slice($rotationOffset)
                            ->concat($collectionImages->slice(0, $rotationOffset))
                            ->values()
                            ->map(fn (string $path): string => asset($path));

                        if ($category->image_path) {
                            $primaryImage = asset($category->image_path);
                            $collectionImages = collect([$primaryImage])
                                ->merge($collectionImages->reject(fn (string $path): bool => $path === $primaryImage))
                                ->values();
                        }
                    @endphp

                    <a
                        class="collection-card"
                        href="{{ route('catalog.collections.show', $category) }}"
                        data-reveal-item
                        data-collection-card
                        data-collection-images='@json($collectionImages)'
                        data-collection-interval="{{ 2400 + (($loop->index % 5) * 180) }}"
                        data-collection-transition="{{ ['fade', 'slide', 'zoom', 'lift', 'blur', 'wipe'][$loop->index % 6] }}"
                    >
                        @if ($collectionImages->isNotEmpty())
                            <span class="collection-media" aria-hidden="true">
                                @foreach ($collectionImages->take(2) as $frameIndex => $collectionImage)
                                    <img
                                        src="{{ $collectionImage }}"
                                        alt=""
                                        loading="lazy"
                                        width="900"
                                        height="900"
                                        data-collection-image
                                        data-collection-layer="{{ $frameIndex }}"
                                        @class(['is-active' => $frameIndex === 0])
                                    >
                                @endforeach
                            </span>
                        @else
                            <span class="image-placeholder" aria-hidden="true"></span>
                        @endif
                        <span class="collection-overlay" aria-hidden="true"></span>
                        <span class="collection-copy">
                            <small>{{ $category->published_products_count }} mẫu đèn</small>
                            <strong>{{ $category->name }}</strong>
                            <span>Khám phá <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                @empty
                    <p class="empty-state">Bộ sưu tập đang được chuẩn bị.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="brand-banner section" aria-labelledby="brand-banner-title" data-brand-banner>
        <div class="shell">
            <header class="brand-banner-heading" data-reveal>
                <div>
                    <p class="eyebrow">{{ $siteContent->get('home_banner_eyebrow') }}</p>
                    <h2 id="brand-banner-title">{{ $siteContent->get('home_banner_title') }}<br><em>{{ $siteContent->get('home_banner_emphasis') }}</em></h2>
                </div>
                <p>{{ $siteContent->get('home_banner_description') }}</p>
            </header>

            <figure class="brand-banner-figure" data-reveal>
                <div class="brand-banner-media" data-ambient>
                    <img
                        src="{{ $siteContent->asset('home_banner_image') }}"
                        alt="{{ $siteContent->get('home_banner_image_alt') }}"
                        loading="lazy"
                        width="1840"
                        height="855"
                        data-parallax="22"
                    >
                    <span class="brand-banner-edition">{{ $siteContent->get('home_banner_edition') }}</span>
                    <a class="brand-banner-link" href="{{ route('catalog.products.index') }}">
                        <span>{{ $siteContent->get('home_banner_cta') }}</span>
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

                <figcaption>
                    <p><span>01</span> Phòng ngủ / Góc đọc / Khoảng nghỉ</p>
                    <p>Ánh sáng được biên tập để sống cùng mỗi ngày.</p>
                </figcaption>
            </figure>
        </div>
    </section>

    <section class="selected-products section" data-product-wave aria-labelledby="selected-products-title">
        <div class="shell">
            @if ($featuredProducts->isNotEmpty())
                <div class="cinematic-wave-window" aria-hidden="true">
                    <div class="cinematic-wave-ribbon" data-wave-ribbon>
                        @for ($tile = 0; $tile < 18; $tile++)
                            @php
                                $waveProduct = $featuredProducts[$tile % $featuredProducts->count()];
                            @endphp
                            @if ($waveProduct->images->first())
                                <div class="cinematic-wave-tile" data-wave-tile><img src="{{ $waveProduct->images->first()->url }}" alt="" loading="lazy" width="160" height="210"></div>
                            @endif
                        @endfor
                    </div>
                    <span class="cinematic-wave-caption">MỖI DÁNG ĐÈN, MỘT NHỊP SÁNG.</span>
                </div>
            @endif
            <div class="catalog-heading" id="selected" data-reveal>
                <div>
                    <p class="eyebrow">{{ $siteContent->get('home_featured_eyebrow') }}</p>
                    <h2 id="selected-products-title">{{ $siteContent->get('home_featured_title') }}</h2>
                </div>
                <p>{{ $siteContent->get('home_featured_description') }}</p>
            </div>

            <div class="catalog-toolbar" aria-label="Thông tin danh mục" data-reveal>
                <p>{{ $featuredProducts->count() }} mẫu đang được giới thiệu</p>
                <a href="#collections">Khám phá theo không gian <span aria-hidden="true">↓</span></a>
            </div>

            <div class="product-grid product-grid-catalog" data-wave-grid>
                @forelse ($featuredProducts as $product)
                    <x-product-card :product="$product" />
                @empty
                    <p class="empty-state">Những sản phẩm đầu tiên đang được chuẩn bị.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="material-story section" aria-labelledby="material-story-title">
        <div class="shell story-grid">
            <div class="story-visual" data-reveal>
                <div class="story-visual-media">
                    <img
                        src="{{ $siteContent->asset('home_story_image') }}"
                        alt="{{ $siteContent->get('home_story_image_alt') }}"
                        loading="lazy"
                        width="1024"
                        height="1024"
                        data-parallax="16"
                    >
                </div>
                <span class="story-visual-label">Linen / Gốm mờ</span>
            </div>

            <div class="story-copy" data-reveal>
                <p class="eyebrow">{{ $siteContent->get('home_story_eyebrow') }}</p>
                <h2 id="material-story-title">{{ $siteContent->get('home_story_title') }}<br><em>{{ $siteContent->get('home_story_emphasis') }}</em></h2>
                <p>{{ $siteContent->get('home_story_body') }}</p>

                <dl class="story-facts">
                    <div>
                        <dt>Ánh sáng</dt>
                        <dd>Tán dịu, không chói mắt</dd>
                    </div>
                    <div>
                        <dt>Bảng màu</dt>
                        <dd>Ấm, trầm, dễ sống cùng</dd>
                    </div>
                    <div>
                        <dt>Không gian</dt>
                        <dd>Phòng ngủ và góc nghỉ</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="light-principles" aria-labelledby="light-principles-title">
        <div class="shell">
            <div class="principles-heading" data-reveal>
                <p class="eyebrow">Cách Clare chọn đèn</p>
                <h2 id="light-principles-title">Bốn điều cho một góc sáng dễ ở.</h2>
            </div>

            <ol class="principles-list" data-reveal-group>
                <li data-reveal-item><span>01</span><strong>Ánh sáng tán dịu</strong><p>Để mắt có thể nghỉ ngơi vào cuối ngày.</p></li>
                <li data-reveal-item><span>02</span><strong>Dáng đèn vừa vặn</strong><p>Cho bàn đầu giường, góc đọc và lối đi nhỏ.</p></li>
                <li data-reveal-item><span>03</span><strong>Màu sắc có chủ đích</strong><p>Mỗi biến thể có SKU, giá và tồn kho riêng.</p></li>
                <li data-reveal-item><span>04</span><strong>Thông tin rõ ràng</strong><p>Xem chất liệu, kích thước và tình trạng hàng trước khi chọn.</p></li>
            </ol>
        </div>
    </section>

    <section class="help-section section" id="services">
        <div class="shell help-card" data-reveal data-ambient>
            <div>
                <p class="eyebrow">{{ $siteContent->get('home_help_eyebrow') }}</p>
                <h2>{{ $siteContent->get('home_help_title') }}</h2>
            </div>
            <div>
                <p>{{ $siteContent->get('home_help_body') }}</p>
                <a class="button button-light" href="{{ route('appointments.create') }}">{{ $siteContent->get('home_help_cta') }}</a>
            </div>
        </div>
    </section>
@endsection
