@php($socialProviders = ['google' => 'Google', 'facebook' => 'Facebook'])
@php($socialProviderIcons = [
    'google' => asset('images/auth/google.svg'),
    'facebook' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAAsTAAALEwEAmpwYAAABlElEQVR4nI1TUUvCUBTej4jeQvot0h/RuWy99ZplkPZg4MZcG0X0VjN6yCALQahQaps5NvSxl/WmQW3GjBS7cSZep5vZBwfGPfd85zvfPSOIAMQFfZHitGSMrZ1DxDltizp8XiDmgeT05QijKLSo9xOSidKFthubeRPRgt6PsIpM8/VQYPGqqK2QjGqnr9po/9YKDMhFWcWieSPs60wyqp0pfviKjh866Npw3BDvOihz845IRrUmlEQYWQ3qXDQcNPhBGAXNGSvJKjI2jBb1fpBkuztwC19aPVRqdtHRfQfn1gWj5xpLcVoykTd9xUzJwp1PKuPCUYCx8DoEPBM4PX2BK9uYIFe2ffnUZQuRTE0iSFbJp6YInG/P4B6cPn3iO7uFFoqx6tlwBMn8F4FXCYxAcfXE0ETBb+K8EbCJANiw6Wf8iwA8i2bVR2IEmq+HYMO8izSLABYJ7m4cNJYwwZDECENipCSIYOYqTyjJKjLMt3Pxit6sLzfgG85Atq9zEMCctZy2nZYalT2pWaV5LTnrd/4FtnR90JuufnsAAAAASUVORK5CYII=',
])
<div class="auth-social-login">
    <div><span></span><small>hoặc tiếp tục với</small><span></span></div>
    <div class="auth-social-buttons">
        @foreach ($socialProviders as $provider => $label)
            @if ($siteSettings->socialProviderConfigured($provider))
                <a class="auth-social-button" href="{{ route('social.redirect', $provider) }}">
                    <img class="auth-social-icon" src="{{ $socialProviderIcons[$provider] }}" width="20" height="20" alt="">
                    <span>{{ $label }}</span>
                </a>
            @else
                <span class="auth-social-button is-disabled" title="Admin chưa cấu hình {{ $label }}">
                    <img class="auth-social-icon" src="{{ $socialProviderIcons[$provider] }}" width="20" height="20" alt="">
                    <span>{{ $label }}</span>
                </span>
            @endif
        @endforeach
    </div>
</div>
