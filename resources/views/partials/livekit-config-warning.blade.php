{{--
    Shared LiveKit setup warning.

    Rendered server-side on every page that leads to the classroom, so a broken
    configuration is visible before anyone clicks Join — and even when the
    LiveKit client script itself never loads.

    Expects:
      $problem      — the diagnose() message (required; include only when non-empty)
      $warningClass — optional wrapper class. Pass it when the page styles its
                      own warning (the classroom does); otherwise the partial
                      brings its own theme-aware styling.
      $warningId    — optional wrapper id, defaulting to "livekitConfigWarning".

    The names are prefixed on purpose: the class-details pages already define a
    `$class` (the ClassModel), which would silently swallow a plain $class here.
--}}
@php
    $wrapperClass = $warningClass ?? null;
    $wrapperId = $warningId ?? 'livekitConfigWarning';
@endphp

@if($wrapperClass === null)
    @once
        <style>
            .livekit-config-warning{display:flex;gap:12px;align-items:flex-start;padding:14px 18px;margin-bottom:16px;border-radius:12px;background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.38);color:var(--dash-text,#eef4ff)}
            .livekit-config-warning>i{color:#f87171;font-size:1.05rem;margin-top:3px}
            .livekit-config-warning strong{display:block;font-size:.9rem;margin-bottom:4px;color:#f87171}
            .livekit-config-warning p{margin:0;font-size:.82rem;line-height:1.6;color:var(--dash-muted,#98a7c4);overflow-wrap:anywhere}
        </style>
    @endonce
@endif

<div class="{{ $wrapperClass ?? 'livekit-config-warning' }}" id="{{ $wrapperId }}" role="alert">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
        <strong>The video service is not configured — joining is disabled.</strong>
        <p>{{ $problem }}</p>
    </div>
</div>
