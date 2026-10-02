@extends('layouts.student')

@section('title', 'My Badges')
@php
    $activeNav = 'badges';
    $pageTitle = 'My Badges';
    $pageIcon = '<i class="fa-solid fa-medal"></i>';
@endphp

@section('content')
<div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="My Badges"
        subtitle="View the badges you've earned for your achievements and milestones."
        icon="fa-medal"
        kicker="Achievements"
    >
        <x-slot name="meta">
            <span>{{ $badges->count() }} badges earned</span>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ BADGES GRID ═══ --}}
    @if($badges->count() > 0)
        <div class="card-grid">
            @foreach($badges as $badgeAward)
                <div class="card card--badge">
                    <div class="card-header">
                        <div class="badge-icon">
                            <i class="fa-solid fa-medal"></i>
                        </div>
                        <h4>{{ $badgeAward->badge->name }}</h4>
                        <span class="badge badge--success">Earned</span>
                    </div>
                    <div class="card-body">
                        <div class="badge-details">
                            <div class="detail-item">
                                <i class="fa-solid fa-info-circle"></i>
                                <span>{{ $badgeAward->badge->description }}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fa-solid fa-calendar"></i>
                                <span>Earned: {{ $badgeAward->issued_at->format('F j, Y') }}</span>
                            </div>
                            @if($badgeAward->badge->criteria)
                                <div class="detail-item">
                                    <i class="fa-solid fa-check-circle"></i>
                                    <span>Criteria: {{ $badgeAward->badge->criteria }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('student.courses.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-book-open"></i> Continue Learning
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $badges->links() }}
    @else
        <div class="card card--empty">
            <div class="card-body">
                <i class="fa-solid fa-medal"></i>
                <h3>No badges earned yet</h3>
                <p>Complete learning milestones to earn badges for your achievements.</p>
                <a href="{{ route('student.courses.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-book-open"></i> Browse Courses
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
