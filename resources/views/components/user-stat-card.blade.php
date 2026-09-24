@php
    $label = $label ?? 'Metric';
    $value = $value ?? 0;
    $icon = $icon ?? 'fa-chart-simple';
    $valueId = $valueId ?? null;
@endphp
<section class="user-stat-card" aria-label="{{ $label }}" {{ $attributes }}>
    <div class="user-stat-top">
        <span class="user-stat-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span>
        @if(isset($trend))<span class="user-stat-trend">{{ $trend }}</span>@endif
    </div>
    <span class="user-stat-label">{{ $label }}</span>
    <strong class="user-stat-value" @if($valueId) id="{{ $valueId }}" @endif>{{ $value }}</strong>
    @if(isset($footer))<span class="user-stat-foot">{{ $footer }}</span>@endif
</section>
