@extends('layouts.storefront', [
    'description' => 'Khám phá đèn bàn và đèn tường Clare — ánh sáng ấm, chất liệu tự nhiên và kiểu dáng nhẹ nhàng cho ngôi nhà.',
    'bodyClass' => 'catalog-home',
])

@section('content')
    <section class="hero home-hero" data-home-hero>
        <div class="shell hero-grid">
            <div class="hero-copy" data-reveal data-reveal-immediate>
                <p class="eyebrow">Clare / Ánh sáng cho căn nhà</p>
                <h1>Đèn dịu.<br>Nhịp sống <em>êm.</em></h1>
                <p class="hero-intro">Khám phá những dáng đèn cho bàn cạnh giường, góc đọc sách và mọi nơi bạn muốn căn phòng trở nên dễ chịu hơn.</p>

                <div class="hero-actions">
                    <a class="button button-primary" href="#selected">Xem bộ sưu tập</a>
                    <a class="text-link" href="#services">Tìm đèn phù hợp</a>
                </div>
            </div>

            <figure class="hero-visual" data-reveal data-reveal-immediate>
                <div class="hero-visual-media">
                    <img
                        src="{{ asset('images/catalog/hero-ru-dem.png') }}"
                        alt="Đèn Ru Đêm tỏa ánh sáng ấm trên tủ gỗ cạnh giường"
                        width="1536"
                        height="1024"
                        data-parallax="18"
                    >
                    <span class="hero-visual-note">Phòng ngủ / Ánh sáng ấm</span>
                    <span class="hero-visual-index" aria-hidden="true">01</span>
                </div>
                <figcaption>
                    <span>Đèn bàn Ru Đêm</span>
                    <a href="{{ route('catalog.products.show', 'ru-dem') }}">Khám phá <span aria-hidden="true">↗</span></a>
                </figcaption>
            </figure>
        </div>
    </section>

    <section class="home-manifesto" aria-labelledby="home-manifesto-title" data-reveal>
        <div class="shell home-manifesto-grid">
            <p class="eyebrow">Chọn ít hơn, sống cùng lâu hơn</p>
            <div>
                <h2 id="home-manifesto-title">Mỗi dáng đèn đều bắt đầu bằng một khoảnh khắc trong nhà.</h2>
                <p>Clare chọn ánh sáng theo cách bạn sử dụng căn phòng: đủ ấm khi đọc, đủ dịu trước giờ ngủ và vừa vặn cho một góc nhỏ của riêng mình.</p>
            </div>
        </div>
    </section>

    <section class="collections section" id="collections">
        <div class="shell">
            <div class="section-heading split-heading collection-section-heading" data-reveal>
                <div>
                    <p class="eyebrow">Khám phá theo không gian</p>
                    <h2>Đặt đúng nơi.<br><em>Sáng đúng cách.</em></h2>
                </div>
                <p>Hai bộ sưu tập nhỏ, được biên tập cho những nhịp sống khác nhau trong một căn nhà.</p>
            </div>

            <div class="collection-grid" data-reveal-group>
                @forelse ($categories as $category)
                    <a class="collection-card" href="{{ route('catalog.collections.show', $category) }}" data-reveal-item>
                        <img
                            src="{{ asset($category->image_path) }}"
                            alt=""
                            loading="lazy"
                            width="900"
                            height="900"
                        >
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
                    <p class="eyebrow">Không gian Clare</p>
                    <h2 id="brand-banner-title">Một dải sáng.<br><em>Nhiều khoảng dịu.</em></h2>
                </div>
                <p>Mỗi chiếc đèn là một điểm sáng nhỏ; khi đặt cạnh nhau, chúng tạo nên nhịp điệu ấm áp cho cả căn phòng.</p>
            </header>

            <figure class="brand-banner-figure" data-reveal>
                <div class="brand-banner-media" data-ambient>
                    <img
                        src="{{ asset('images/catalog/banner.png') }}"
                        alt="Không gian phòng ngủ Clare với nhiều mẫu đèn tỏa ánh sáng ấm"
                        loading="lazy"
                        width="1840"
                        height="855"
                        data-parallax="22"
                    >
                    <span class="brand-banner-edition">The Clare edit / 2026</span>
                    <a class="brand-banner-link" href="{{ route('catalog.products.index') }}">
                        <span>Xem tất cả đèn</span>
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

    <section class="selected-products section" id="selected">
        <div class="shell">
            <div class="catalog-heading" data-reveal>
                <div>
                    <p class="eyebrow">Danh mục Clare</p>
                    <h2>Những mẫu đèn<br>được chọn.</h2>
                </div>
                <p>Chọn màu, kiểm tra tồn kho và tìm dáng đèn hợp với khoảng nghỉ của bạn.</p>
            </div>

            <div class="catalog-toolbar" aria-label="Thông tin danh mục" data-reveal>
                <p>{{ $featuredProducts->count() }} mẫu đang được giới thiệu</p>
                <a href="#collections">Khám phá theo không gian <span aria-hidden="true">↓</span></a>
            </div>

            <div class="product-grid product-grid-catalog" data-reveal-group>
                @forelse ($featuredProducts as $product)
                    <x-product-card :product="$product" reveal />
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
                        src="{{ asset('images/catalog/den-ban-thao-moc.png') }}"
                        alt="Chi tiết chụp linen và thân gốm của đèn Thảo Mộc"
                        loading="lazy"
                        width="1024"
                        height="1024"
                        data-parallax="16"
                    >
                </div>
                <span class="story-visual-label">Linen / Gốm mờ</span>
            </div>

            <div class="story-copy" data-reveal>
                <p class="eyebrow">Chất liệu tạo nên cảm giác</p>
                <h2 id="material-story-title">Đẹp khi nhìn.<br><em>Dịu khi sống cùng.</em></h2>
                <p>Gốm mờ, linen dệt thô, thủy tinh opal và kim loại phủ màu trầm được kết hợp để ánh sáng không chỉ hiện ra — mà còn có chiều sâu.</p>

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
                <p class="eyebrow">Cần một chút trợ giúp?</p>
                <h2>Hãy kể Clare nghe về căn phòng của bạn.</h2>
            </div>
            <div>
                <p>Chúng tôi sẽ cùng bạn cân nhắc kích thước, nhiệt độ màu và vị trí đặt đèn. Hãy gửi thời gian mong muốn, Clare sẽ xem lại và xác nhận thủ công.</p>
                <a class="button button-light" href="{{ route('appointments.create') }}">Gửi yêu cầu tư vấn</a>
            </div>
        </div>
    </section>
@endsection
