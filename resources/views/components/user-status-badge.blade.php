@php
    $status = strtolower((string) ($status ?? 'unknown'));
    $label = $label ?? ucfirst(str_replace('_', ' ', $status));
@endphp
<span class="user-status {{ $status }}"><i class="fa-solid {{ in_array($status, ['active','published','completed']) ? 'fa-circle-check' : ($status === 'pending' ? 'fa-clock' : 'fa-circle') }}" aria-hidden="true"></i>{{ $label }}</span>
