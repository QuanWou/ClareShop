@php($socialProviders = ['google' => 'Google', 'facebook' => 'Facebook'])
<div class="auth-social-login">
    <div><span></span><small>hoặc tiếp tục với</small><span></span></div>
    <div class="auth-social-buttons">
        @foreach ($socialProviders as $provider => $label)
            @if ($siteSettings->socialProviderConfigured($provider))
                <a class="auth-social-button" href="{{ route('social.redirect', $provider) }}"><strong>{{ $provider === 'google' ? 'G' : 'f' }}</strong><span>{{ $label }}</span></a>
            @else
                <span class="auth-social-button is-disabled" title="Admin chưa cấu hình {{ $label }}"><strong>{{ $provider === 'google' ? 'G' : 'f' }}</strong><span>{{ $label }}</span></span>
            @endif
        @endforeach
    </div>
</div>
