@php
    $flash = session('status') ?? session('success') ?? session('warning') ?? session('error');
    $flashKey = null;
    if (session('status') !== null) {
        $flashKey = 'success';
    } elseif (session('success') !== null) {
        $flashKey = 'success';
    } elseif (session('warning') !== null) {
        $flashKey = 'warning';
    } elseif (session('error') !== null) {
        $flashKey = 'error';
    }
    $flashLabel = match ($flashKey) {
        'warning' => 'Warning',
        'error' => 'Error',
        default => 'Success',
    };
@endphp

@if ($flash !== null)
    <div class="toast {{ $flashKey ?? 'success' }} show" role="alert">
        <div class="toast-label">
            <div class="toast-dot"></div>
            <span>{{ $flashLabel }}</span>
        </div>
        <div class="toast-msg">{{ $flash }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="toast error show" role="alert">
        <div class="toast-label">
            <div class="toast-dot"></div>
            <span>Error</span>
        </div>
        <div class="toast-msg">{{ implode(', ', $errors->all()) }}</div>
    </div>
@endif
