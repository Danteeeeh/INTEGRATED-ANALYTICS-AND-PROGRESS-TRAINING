@extends('layouts.instructor')

@section('title', 'All Upcoming Activities')
@php
    $activeNav = 'dashboard';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-list-check"></i>
            All Upcoming Activities
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Upcoming assignments, quizzes, exams and virtual classes ({{ $activities->count() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            Everything scheduled ahead across your classes, not just the five rows shown on the dashboard.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Activity</th>
                        <th>Scheduled</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity['type'] }}</td>
                            <td>{{ $activity['title'] }}</td>
                            <td>{{ $activity['date'] }}</td>
                            <td style="white-space:nowrap;">
                                @if(! empty($activity['url']) && $activity['url'] !== '#')
                                    <a href="{{ $activity['url'] }}" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:20px;color:#64748b;">
                                No upcoming activities.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
