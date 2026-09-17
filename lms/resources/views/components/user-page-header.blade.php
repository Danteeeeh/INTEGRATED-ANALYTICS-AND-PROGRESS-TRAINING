@php
    $title = $title ?? 'Page';
    $subtitle = $subtitle ?? null;
    $icon = $icon ?? 'fa-layer-group';
    $accent = $accent ?? 'var(--user-accent)';
@endphp
<section class="user-hero" style="--page-accent: {{ $accent }}" aria-labelledby="user-page-title">
    <div class="user-hero-copy">
        <div class="user-kicker"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $kicker ?? ucfirst(auth()->user()?->role?->slug ?? 'User') }}</div>
        <h1 id="user-page-title">{{ $title }}</h1>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
        @if(isset($meta))<div class="user-hero-meta">{{ $meta }}</div>@endif
    </div>
    @if(isset($actions))<div class="user-hero-actions">{{ $actions }}</div>@endif
</section>
