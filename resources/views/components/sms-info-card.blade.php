@php
    $details ??= [];
    $buttonLink ??= null;
@endphp

<div class="info-card">
  <div class="card-label">
    @if($icon)
      <i class="{{ $icon }}"></i>
    @endif
    {{ $label }}
  </div>
  @if($name)
    <div class="card-name">{{ $name }}</div>
  @endif
  @if($amount)
    <div class="card-amount">{{ $amount }}</div>
  @endif
  @if($status)
    <div class="card-status">{{ $status }}</div>
  @endif
  @if(isset($details) && is_iterable($details))
    @foreach($details as $detail)
      <div class="card-detail">{{ $detail }}</div>
    @endforeach
  @endif
  @if($buttonText && $buttonLink)
    <a href="{{ $buttonLink }}" class="card-btn">
      <i class="fa-solid fa-arrow-right"></i>
      {{ $buttonText }}
    </a>
  @endif
</div>