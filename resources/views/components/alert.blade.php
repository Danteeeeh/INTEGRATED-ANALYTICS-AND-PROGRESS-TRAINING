@props(['type' => 'info', 'message' => ''])

@php
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
    ];
@endphp

<div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $styles[$type] ?? $styles['info'] }}">
    {{ $message ?: $slot }}
</div>
