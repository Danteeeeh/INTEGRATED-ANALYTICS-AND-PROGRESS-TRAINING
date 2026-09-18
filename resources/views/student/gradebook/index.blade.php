@extends('layouts.student')

@section('title', 'My Grades')
@php $activeNav = 'gradebook'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Grades"
        subtitle="{{ $class->course?->title ?? $class->code }} — released grades"
        icon="fa-graduation-cap"
    >
        <x-slot name="meta">
            <span class="user-status active">{{ number_format($overallPercent, 1) }}% overall</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.classes.show', $class) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-stat-grid">
        <x-user-stat-card label="Overall" value="{{ number_format($overallPercent, 1) }}%" icon="fa-chart-line" trend="Average" />
        <x-user-stat-card label="Earned" value="{{ number_format($earnedPoints, 1) }}" icon="fa-star" footer="weighted points" />
        <x-user-stat-card label="Possible" value="{{ number_format($totalPoints, 1) }}" icon="fa-bullseye" footer="weighted points" />
        <x-user-stat-card label="Released Items" value="{{ $gradeItems->count() }}" icon="fa-file-lines" footer="grade items" />
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-table"></i> Grade Breakdown</h3>
            <span class="user-status">{{ $gradeItems->count() }} items</span>
        </div>
        <div class="user-panel-body">
            @if($gradeItems->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th>Percent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gradeItems as $item)
                                @php
                                    $grade = $grades->get($item->id);
                                    $percent = $grade && $item->max_points > 0
                                        ? ($grade->points / $item->max_points) * 100
                                        : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $item->name }}</div>
                                        <div class="user-email">{{ $item->description ?? '' }}</div>
                                    </td>
                                    <td>{{ $item->category?->name ?? '—' }}</td>
                                    <td>
                                        @if($grade)
                                            {{ $grade->points }}/{{ $item->max_points }}
                                        @else
                                            <span class="user-email">Not graded</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($percent !== null)
                                            <strong>{{ number_format($percent, 1) }}%</strong>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-table"
                    title="No grades released yet"
                    description="Your instructor has not released any grades for this class yet."
                />
            @endif
        </div>
    </div>

    @if($class->course)
        <div class="user-actions">
            <a href="{{ route('student.courses.show', $class->course) }}" class="btn btn-secondary"><i class="fa-solid fa-book"></i> Back to Course</a>
        </div>
    @endif
</div>
@endsection
