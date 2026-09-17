@php
    $icon = $icon ?? 'fa-inbox';
    $title = $title ?? 'Nothing here yet';
    $description = $description ?? null;
@endphp
<div class="user-empty">
    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
    <h3>{{ $title }}</h3>
    @if($description)<p>{{ $description }}</p>@endif
    @if(isset($action)){{ $action }}@endif
</div>
