@props(['type' => 'info', 'message' => ''])

@php
    $icons = [
        'success' => 'fa-circle-check',
        'error' => 'fa-circle-exclamation',
        'info' => 'fa-circle-info',
        'warning' => 'fa-triangle-exclamation',
    ];
    $styles = [
        'success' => 'lms-alert-success',
        'error' => 'lms-alert-error',
        'info' => 'lms-alert-info',
        'warning' => 'lms-alert-warning',
    ];
@endphp

<div class="lms-alert {{ $styles[$type] ?? $styles['info'] }}" role="alert" data-alert>
    <i class="fa-solid {{ $icons[$type] ?? $icons['info'] }}" aria-hidden="true"></i>
    <div class="lms-alert-body">{{ $message ?: $slot }}</div>
    @if(isset($dismiss))
        <button type="button" class="lms-alert-dismiss" data-dismiss aria-label="Dismiss alert"><i class="fa-solid fa-xmark"></i></button>
    @endif
</div>
