@props([
    'theme' => 'auto',
    'size' => 'normal',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'turnstile-wrapper']) }} style="display: flex; justify-content: center; margin: 1rem 0;">
    <div class="cf-turnstile"
         data-sitekey="{{ config('services.turnstile.key') }}"
         data-theme="{{ $theme }}"
         data-size="{{ $size }}"
         @if($action) data-action="{{ $action }}" @endif>
    </div>
</div>

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endonce
