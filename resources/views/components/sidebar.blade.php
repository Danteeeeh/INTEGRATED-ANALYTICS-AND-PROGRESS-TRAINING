@props(['items' => []])

<div class="flex items-center gap-2 px-5 h-16 border-b border-slate-800">
    <span class="text-lg font-semibold tracking-tight">LMS</span>
</div>

<nav class="flex-1 overflow-y-auto py-4" aria-label="Main navigation">
    <ul class="space-y-1 px-3">
        @foreach ($items as $item)
            <li>
                @php
                    $active = $item['route'] && request()->routeIs($item['route']);
                @endphp
                <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   {{ $active ? 'aria-current="page"' : '' }}>
                    <span class="w-4 h-4 rounded-sm bg-current opacity-70 shrink-0"></span>
                    <span>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
