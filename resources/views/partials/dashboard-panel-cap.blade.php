@php
    $count = $count ?? null;
    $shown = $shown ?? null;
    $total = $total ?? null;
    $href = $href ?? null;
    $hintClass = trim('panel-cap-note ' . ($hintClass ?? ''));
    $viewAllUrl = $viewAllUrl ?? null;
    $viewAllLabel = $viewAllLabel ?? 'View all';
    $viewAllOnlyWhenCapped = $viewAllOnlyWhenCapped ?? false;
    $isCapped = $total !== null && $shown !== null && $total > $shown;
    $showViewAll = $viewAllUrl && (! $viewAllOnlyWhenCapped || $isCapped);
@endphp

@if($count !== null || ($isCapped && $href) || $showViewAll)
    <div class="panel-cap-actions">
        @if($count !== null)
            <span class="panel-count"@if(! empty($countId)) id="{{ $countId }}"@endif>{{ $count }}</span>
        @endif
        @if($isCapped && $href)
            <a class="{{ $hintClass }}" href="{{ $href }}">Showing {{ $shown }} of {{ $total }}</a>
        @endif
        @if($showViewAll)
            <a href="{{ $viewAllUrl }}" class="view-all">{{ $viewAllLabel }}</a>
        @endif
    </div>
@endif
