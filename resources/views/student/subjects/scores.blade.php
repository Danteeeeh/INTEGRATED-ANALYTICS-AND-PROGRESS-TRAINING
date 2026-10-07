@extends('layouts.student')

@php
    $activeNav = 'subjects';
    $pageTitle = 'Check Available Scores';
    $pageIcon = '<i class="fa-solid fa-clipboard-check"></i>';
@endphp

@section('title', $pageTitle)

{{--
    Student view of "Check Available Scores".

    Shows only the signed-in student's own released grades. It must never
    render a classmate's row, the class average, or cohort-wide counts —
    SubjectController::scores() therefore hands this view a per-student
    payload (SubjectService::studentScoreSheet) instead of the cohort report.
--}}
@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Scores"
        subtitle="{{ $class->course?->title ?? $class->code }} — your released grades"
        icon="fa-clipboard-check"
    >
        <x-slot name="meta">
            @if ($summary['is_graded'] ?? false)
                <span class="user-status active">{{ number_format((float) $summary['percent'], 1) }}&percnt; overall</span>
            @else
                <span class="user-status">Not graded yet</span>
            @endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ url()->previous() ?: route('student.subjects.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to list
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-stat-grid">
        <x-user-stat-card
            label="Overall"
            value="{{ ($summary['is_graded'] ?? false) ? number_format((float) $summary['percent'], 1).'%' : '—' }}"
            icon="fa-chart-line"
            trend="{{ ($summary['is_graded'] ?? false) ? ($summary['letter_grade'] ?? '—') : 'Not graded yet' }}"
        />
        <x-user-stat-card
            label="Earned"
            value="{{ number_format((float) ($summary['earned_points'] ?? 0), 1) }}"
            icon="fa-star"
            footer="weighted points"
        />
        <x-user-stat-card
            label="Possible"
            value="{{ number_format((float) ($summary['max_points'] ?? 0), 1) }}"
            icon="fa-bullseye"
            footer="weighted, graded items"
        />
        <x-user-stat-card
            label="Released Items"
            value="{{ $item_count }}"
            icon="fa-file-lines"
            footer="{{ $graded_count }} graded"
        />
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-table"></i> My Grade Breakdown</h3>
            <span class="user-status">{{ $item_count }} {{ \Illuminate\Support\Str::plural('item', $item_count) }}</span>
        </div>
        <div class="user-panel-body">
            @if ($rows->isNotEmpty())
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Score</th>
                                <th>Max</th>
                                <th>Percent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $item = $row['item']; @endphp
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $item->title }}</div>
                                        @if ($item->description)
                                            <div class="user-email">{{ $item->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $item->item_type ?? 'other')) }}</td>
                                    <td>
                                        @if ($row['points'] !== null)
                                            {{ rtrim(rtrim(number_format((float) $row['points'], 2), '0'), '.') }}
                                        @else
                                            <span class="user-email">—</span>
                                        @endif
                                    </td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->max_points, 2), '0'), '.') }}</td>
                                    <td>
                                        @if ($row['percent'] !== null)
                                            <strong>{{ number_format((float) $row['percent'], 1) }}%</strong>
                                        @else
                                            <span class="user-email">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['points'] !== null)
                                            <span class="user-status active"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Graded</span>
                                        @else
                                            <span class="user-status pending"><i class="fa-solid fa-clock" aria-hidden="true"></i>Pending</span>
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
                    description="Your instructor has not released any grades for this subject yet. Check back later."
                />
            @endif
        </div>
    </div>

    @if ($class->course)
        <div class="user-actions">
            <a href="{{ route('student.courses.show', $class->course) }}" class="btn btn-secondary">
                <i class="fa-solid fa-book"></i> Back to Course
            </a>
        </div>
    @endif
</div>
@endsection