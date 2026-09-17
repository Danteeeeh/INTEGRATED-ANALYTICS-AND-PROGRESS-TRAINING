@php
    $buttonLink ??= null;
    $height ??= '300px';
@endphp

<div class="chart-card">
  <div class="chart-header">
    <div>
      <h3>{{ $title }}</h3>
      @if($subtitle)
        <div class="chart-sub">{{ $subtitle }}</div>
      @endif
    </div>
    @if($buttonText && $buttonLink)
      <a href="{{ $buttonLink }}" class="card-btn">
        <i class="fa-solid fa-arrow-right"></i>
        {{ $buttonText }}
      </a>
    @endif
  </div>
  <div class="chart-wrap" style="height: {{ $height }}">
    <canvas id="{{ $chartId }}"></canvas>
  </div>
</div>