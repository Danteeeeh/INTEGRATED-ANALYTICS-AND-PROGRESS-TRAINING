@extends('layouts.student')

@section('title', $plan->title)
@php $activeNav = 'learning_plans'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        :title="$plan->title"
        subtitle="Your AI-assisted learning plan — check off items as you complete them."
        icon="fa-route"
    >
        <x-slot name="actions">
            <a href="{{ route('student.learning-plans.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> All Plans</a>
        </x-slot>
    </x-user-page-header>

    @php
        $items = $plan->items ?? collect();
        $completed = $items->where('status', 'completed')->count();
        $progress = $items->count() > 0 ? round(($completed / $items->count()) * 100) : 0;
    @endphp

    <div class="user-stat-card" style="margin-bottom:18px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <div class="user-kicker">Status · {{ $plan->status }}</div>
                <strong style="font-size:1.1rem">{{ $plan->description }}</strong>
            </div>
            <div style="text-align:right">
                <strong style="font-size:1.4rem">{{ $progress }}%</strong><br>
                <span class="text-muted">{{ $completed }}/{{ $items->count() }} items completed</span>
            </div>
        </div>
        <div class="learning-progress" style="margin-top:12px"><span style="width: {{ $progress }}%"></span></div>
    </div>

    <div class="learning-card">
        <h3 style="margin-bottom:14px"><i class="fa-solid fa-list-check"></i> Plan items</h3>

        @if($items->count() > 0)
            <ul style="list-style:none;padding:0;margin:0">
                @foreach($items as $item)
                    <li style="display:flex;align-items:center;gap:12px;padding:12px 4px;border-bottom:1px solid var(--border);">
                        <form method="POST" action="{{ route('student.learning-plans.items.update', [$plan, $item]) }}" style="display:flex;align-items:center;gap:12px;flex:1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $item->status === 'completed' ? 'pending' : 'completed' }}">
                            <button type="submit" class="btn btn-sm {{ $item->status === 'completed' ? 'btn-success' : 'btn-secondary' }}" style="flex:0 0 auto" aria-label="Toggle item completion">
                                <i class="fa-solid {{ $item->status === 'completed' ? 'fa-square-check' : 'fa-square' }}"></i>
                            </button>
                        </form>
                        <div style="flex:1;min-width:0">
                            <strong>{{ $item->title }}</strong>
                            @if($item->description)
                                <p style="margin:2px 0 0;color:var(--muted-foreground)">{{ $item->description }}</p>
                            @endif
                        </div>
                        <div style="flex:0 0 auto;text-align:right">
                            <x-user-status-badge status="{{ $item->status }}" />
                            <div class="text-muted" style="font-size:.78rem;margin-top:4px">
                                {{ optional($item->target_date)->format('M j') ?? '—' }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <x-user-empty-state
                icon="fa-list-check"
                title="No items yet"
                description="Refresh your plan to generate items from your latest performance signals."
            />
        @endif
    </div>
</div>
@endsection