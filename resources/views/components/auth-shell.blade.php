@props([
'eyebrow',
'headingId',
'intro',
'title',
'variant' => 'login',
'visualCopy',
'visualEyebrow',
'visualItems' => [],
'visualTitle',
])

<section class="auth-page auth-page--{{ $variant }}" aria-labelledby="{{ $headingId }}">
    <aside class="auth-visual" aria-label="Không gian ánh sáng Clare">
        <img
            class="auth-visual-image"
            src="{{ asset('images/catalog/login.png') }}"
            alt=""
            aria-hidden="true">
        <span class="auth-visual-glow" aria-hidden="true"></span>

        <div class="auth-visual-copy">
            <p class="auth-visual-eyebrow">{{ $visualEyebrow }}</p>
            <h2>{{ $visualTitle }}</h2>
            <p>{{ $visualCopy }}</p>

            @if ($visualItems !== [])
            <ul class="auth-benefits" role="list">
                @foreach ($visualItems as $item)
                <li><span aria-hidden="true"></span>{{ $item }}</li>
                @endforeach
            </ul>
            @endif
        </div>

        <p class="auth-visual-signature">Ánh sáng cho những khoảng nghỉ</p>
    </aside>

    <div class="auth-panel">
        <div class="auth-card">
            <a class="auth-wordmark" href="{{ route('catalog.home') }}" aria-label="Clare — về trang chủ">CLARE</a>

            <div class="auth-heading">
                <p class="eyebrow">{{ $eyebrow }}</p>
                <h1 id="{{ $headingId }}">{{ $title }}</h1>
                <p class="auth-intro">{{ $intro }}</p>
            </div>

            @if ($errors->any())
            <div class="auth-alert" role="alert" tabindex="-1" data-auth-alert>
                <span class="auth-alert-mark" aria-hidden="true">!</span>
                <div>
                    <strong>Mình cần bạn kiểm tra lại một chút.</strong>
                    <p>{{ $errors->first() }}</p>
                </div>
            </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</section>
