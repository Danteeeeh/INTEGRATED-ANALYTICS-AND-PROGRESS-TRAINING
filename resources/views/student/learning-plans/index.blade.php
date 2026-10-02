@extends('layouts.student')

@section('title', 'My Learning Plans')
@php $activeNav = 'learning_plans'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Learning Plans"
        subtitle="Personalized plans built from your performance signals to guide your next steps."
        icon="fa-route"
    >
        <x-slot name="actions">
            <form method="POST" action="{{ route('student.learning-plans.generate') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Create / Refresh Plan
                </button>
            </form>
        </x-slot>
    </x-user-page-header>

    @if($plans->count() > 0)
        <div class="learning-grid">
            @foreach($plans as $plan)
                @php
                    $items = $plan->items ?? collect();
                    $completed = $items->where('status', 'completed')->count();
                    $progress = $items->count() > 0 ? round(($completed / $items->count()) * 100) : 0;
                @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            {{ $plan->status }} · target {{ optional($plan->target_date)->format('M j, Y') ?? '—' }}
                        </div>
                        <h3>{{ $plan->title }}</h3>
                        <p>{{ $plan->description }}</p>
                    </div>
                    <div>
                        <div class="learning-progress"><span style="width: {{ $progress }}%"></span></div>
                        <div class="user-actions" style="justify-content:space-between;margin-top:10px">
                            <span class="text-muted">{{ $completed }}/{{ $items->count() }} items · {{ $progress }}%</span>
                            <a href="{{ route('student.learning-plans.show', $plan) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> Open Plan</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-user-empty-state
            icon="fa-route"
            title="No learning plan yet"
            description="Generate a personalized plan and we will map your risk signals into concrete next steps."
        >
            <x-slot name="action">
                <form method="POST" action="{{ route('student.learning-plans.generate') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-wand-magic-sparkles"></i> Create My Plan</button>
                </form>
            </x-slot>
        </x-user-empty-state>
    @endif
</div>
@endsection