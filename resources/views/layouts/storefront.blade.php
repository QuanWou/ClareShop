<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? $siteContent->get('global_meta_description') }}">
        <meta name="theme-color" content="#f3eee5">

        <title>{{ isset($title) ? $title.' — '.$siteContent->get('global_site_name') : $siteContent->get('global_site_name').' — Ánh sáng cho những khoảng nghỉ' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="{{ $bodyClass ?? '' }}">
        <a class="skip-link" href="#main-content">Đi đến nội dung chính</a>

        <div class="announcement" role="note">
            <p>{{ $siteContent->get('global_announcement') }}</p>
        </div>

        <header class="site-header">
            <div class="shell header-inner">
                <a class="wordmark" href="{{ route('catalog.home') }}" aria-label="{{ $siteContent->get('global_site_name') }} — về trang chủ">{{ $siteContent->get('global_site_name') }}</a>

                <nav class="desktop-nav" aria-label="Điều hướng chính">
                    <details class="nav-menu">
                        <summary aria-expanded="false">Đèn</summary>
                        <div class="nav-dropdown">
                            <a href="{{ route('catalog.products.index') }}">Tất cả đèn</a>
                            <a href="{{ route('catalog.collections.show', 'den-ban') }}">Đèn bàn</a>
                            <a href="{{ route('catalog.collections.show', 'den-tuong') }}">Đèn tường</a>
                        </div>
                    </details>

                    <details class="nav-menu">
                        <summary aria-expanded="false">Phụ kiện</summary>
                        <div class="nav-dropdown">
                            <a href="{{ route('catalog.home') }}#collections">Bộ sưu tập</a>
                            <a href="{{ route('catalog.home') }}#selected">Mẫu được chọn</a>
                        </div>
                    </details>

                    <details class="nav-menu">
                        <summary aria-expanded="false">Khám phá</summary>
                        <div class="nav-dropdown">
                            <a href="{{ route('catalog.home') }}#collections">Bộ sưu tập</a>
                            <a href="{{ route('catalog.home') }}#about">Về Clare</a>
                        </div>
                    </details>

                    <details class="nav-menu">
                        <summary aria-expanded="false">Cảm hứng</summary>
                        <div class="nav-dropdown">
                            <a href="{{ route('catalog.home') }}#selected">Góc nghỉ ngơi</a>
                            <a href="{{ route('catalog.home') }}#services">Không gian của bạn</a>
                        </div>
                    </details>

                    <a class="nav-consultation" href="{{ route('appointments.create') }}">{{ $siteContent->get('global_consultation_label') }}</a>
                </nav>

                <form class="header-search-panel" id="header-search-panel" action="{{ route('catalog.search') }}" method="get" role="search" aria-hidden="true" data-header-search-form>
                    <label for="header-search-query">Tìm sản phẩm</label>
                    <input id="header-search-query" name="q" type="search" placeholder="{{ $siteContent->get('global_search_placeholder') }}" minlength="2" maxlength="80" required autocomplete="off" data-search-input>
                    <button class="header-search-submit" type="submit">Tìm kiếm</button>
                    <button class="header-search-close" type="button" aria-label="Đóng tìm kiếm" data-search-close>
                        <span aria-hidden="true">×</span>
                    </button>
                </form>

                <div class="header-tools" aria-label="Tiện ích cửa hàng">
                    <button class="header-icon-button" type="button" aria-label="Mở tìm kiếm sản phẩm" aria-controls="header-search-panel" aria-expanded="false" data-search-open>
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 64 64" fill="none">
                            <path d="M47.16 28.58A18.58 18.58 0 1 1 28.58 10a18.58 18.58 0 0 1 18.58 18.58zM54 54L41.94 42" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                    </button>

                    @auth
                        <a class="account-access" href="{{ route('account.show') }}" aria-label="Mở tài khoản Clare">
                            <svg aria-hidden="true" focusable="false" viewBox="0 0 64 64" fill="none">
                                <path d="M35 39.84v-2.53c3.3-1.91 6-6.66 6-11.41 0-7.63 0-13.82-9-13.82s-9 6.19-9 13.82c0 4.75 2.7 9.51 6 11.41v2.53c-10.18.85-18 6-18 12.16h42c0-6.19-7.82-11.31-18-12.16z" fill="currentColor" />
                            </svg>
                            <span>Tài khoản</span>
                        </a>
                    @else
                        <div class="guest-access" aria-label="Tài khoản Clare">
                            <a href="{{ route('login') }}">Đăng nhập</a>
                            <a href="{{ route('register') }}">Đăng ký</a>
                        </div>
                    @endauth

                    <a
                        class="cart-preview"
                        href="{{ route('cart.show') }}"
                        aria-label="Giỏ hàng, hiện có {{ $cartItemCount ?? 0 }} sản phẩm"
                        data-cart-preview
                    >
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M18.9442 3.78037L3.97415 2.86174L3.69961 1.67219C3.63642 1.3981 3.45415 1.16628 3.20279 1.04083L1.33552 0.108556C0.8732 -0.103641 0.325918 0.0894135 0.0990648 0.544716C-0.127789 1.00002 0.0476897 1.55319 0.495516 1.79447L1.96642 2.53447L4.68915 14.3413C4.78779 14.7686 5.16779 15.0713 5.60642 15.0713H16.9005C17.4207 15.0713 17.8423 14.6496 17.8423 14.1295C17.8423 13.6093 17.4207 13.1876 16.9005 13.1876H6.35597L6.1387 12.2454H17.3719C17.8914 12.2454 18.3892 11.8317 18.4846 11.3208L19.7096 4.75719C19.8046 4.24992 19.4623 3.80947 18.9442 3.78037ZM16.7468 10.3613L5.70224 10.3617L4.41406 4.77628L17.6382 5.58583L16.7468 10.3613Z" fill="currentColor" />
                            <path d="M16.3974 17.8973C16.3974 18.6621 15.778 19.2814 15.0151 19.2814C14.2521 19.2814 13.6328 18.6621 13.6328 17.8973C13.6328 17.1324 14.2521 16.5132 15.0151 16.5132C15.778 16.5132 16.3974 17.1324 16.3974 17.8973Z" stroke="currentColor" />
                            <path d="M7.92861 17.8973C7.92861 18.6621 7.30929 19.2814 6.54634 19.2814C5.78338 19.2814 5.16406 18.6621 5.16406 17.8973C5.16406 17.1324 5.78338 16.5132 6.54634 16.5132C7.30929 16.5132 7.92861 17.1324 7.92861 17.8973Z" stroke="currentColor" />
                        </svg>
                        <span class="cart-count" aria-hidden="true" data-cart-count>{{ $cartItemCount ?? 0 }}</span>
                    </a>

                    <details class="mobile-menu" data-mobile-menu>
                        <summary aria-label="Mở điều hướng" aria-controls="mobile-navigation" aria-expanded="false">Menu</summary>
                        <nav id="mobile-navigation" aria-label="Điều hướng trên thiết bị di động">
                            <a href="{{ route('catalog.products.index') }}">Tất cả đèn</a>
                            <a href="{{ route('catalog.collections.show', 'den-ban') }}">Đèn bàn</a>
                            <a href="{{ route('catalog.collections.show', 'den-tuong') }}">Đèn tường</a>
                            <a href="{{ route('appointments.create') }}">Tư vấn</a>
                            @auth
                                <a href="{{ route('account.show') }}">Tài khoản</a>
                                <form class="mobile-logout-form" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit">Đăng xuất</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}">Đăng nhập</a>
                                <a href="{{ route('register') }}">Đăng ký</a>
                            @endauth
                        </nav>
                    </details>
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1">
            @if (! ($inlineNotices ?? false) && (session('success') || $errors->any()))
                <div class="shell notice-stack" aria-live="polite">
                    @if (session('success'))
                        <p class="storefront-notice is-success">{{ session('success') }}</p>
                    @endif

                    @if ($errors->any())
                        <div class="storefront-notice is-error" role="alert">
                            <p>Vui lòng kiểm tra lại:</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer" id="about">
            <div class="shell footer-grid">
                <div>
                    <a class="wordmark footer-wordmark" href="{{ route('catalog.home') }}">{{ $siteContent->get('global_site_name') }}</a>
                    <p>{{ $siteContent->get('global_footer_description') }}</p>
                </div>

                <div>
                    <p class="footer-label">Khám phá</p>
                    <a href="{{ route('catalog.home') }}#collections">Bộ sưu tập</a>
                    <a href="{{ route('catalog.home') }}#selected">Sản phẩm được chọn</a>
                </div>

                <div>
                    <p class="footer-label">Liên hệ</p>
                    <a class="footer-contact-email" href="mailto:{{ $siteContent->get('global_contact_email') }}">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="5" width="18" height="14" rx="1.5" stroke="currentColor" stroke-width="1.6" />
                            <path d="m4 7 7.05 5.29a1.6 1.6 0 0 0 1.9 0L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ $siteContent->get('global_contact_email') }}</span>
                    </a>
                    <a href="{{ route('appointments.create') }}">Tư vấn ánh sáng</a>
                </div>

                <div>
                    <p class="footer-label">Theo dõi Clare</p>
                    <ul class="footer-social-list" aria-label="Các kênh mạng xã hội của Clare">
                        <li>
                            <span class="footer-social-link is-pending" aria-label="Facebook — đang cập nhật kênh chính thức">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13.6 21v-8h2.7l.4-3.1h-3.1v-2c0-.9.25-1.5 1.55-1.5H17V3.62c-.31-.04-1.35-.12-2.56-.12-2.54 0-4.28 1.55-4.28 4.39v2H7.28V13h2.88v8h3.44Z" />
                                </svg>
                                <span>Facebook</span>
                            </span>
                        </li>
                        <li>
                            <span class="footer-social-link is-pending" aria-label="Instagram — đang cập nhật kênh chính thức">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="3.5" width="17" height="17" rx="4.5" stroke="currentColor" stroke-width="1.7" />
                                    <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.7" />
                                    <circle cx="17.35" cy="6.75" r="1" fill="currentColor" />
                                </svg>
                                <span>Instagram</span>
                            </span>
                        </li>
                        <li>
                            <span class="footer-social-link is-pending" aria-label="X (Twitter) — đang cập nhật kênh chính thức">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 4.5 18.75 19.5M18.75 4.5 5 19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                                <span>X (Twitter)</span>
                            </span>
                        </li>
                    </ul>
                    <p class="footer-social-note">Các kênh chính thức đang được cập nhật.</p>
                </div>
            </div>

            <div class="shell footer-bottom">
                <span>© {{ now()->year }} {{ $siteContent->get('global_site_name') }}.</span>
                <span>{{ $siteContent->get('global_footer_signature') }}</span>
            </div>
        </footer>
    </body>
</html>
