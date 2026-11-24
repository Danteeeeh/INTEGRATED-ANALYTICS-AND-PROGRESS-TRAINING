@extends('layouts.instructor')

@section('title', 'All Virtual Classes')
@php
    $activeNav = 'virtual_classes';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            All Virtual Classes
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if($livekitProblem)
        @include('partials.livekit-config-warning', ['problem' => $livekitProblem])
    @endif

    <div class="crud-card">
        <div class="crud-header">
            <h3>Every virtual class in your classes ({{ $virtualClasses->total() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            This is the full list across all your classes. Open a row to manage its room and attendance.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Title</th>
                        <th>Date / Time</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Attendees</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($virtualClasses as $vc)
                        <tr>
                            <td>{{ $vc->class?->code ?? '—' }}</td>
                            <td>
                                {{ $vc->title }}
                                @if($vc->course?->code)
                                    <div style="font-size:.72rem;color:#64748b;">{{ $vc->course->code }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $vc->meeting_date?->format('M d, Y') ?? 'TBD' }}
                                <div style="font-size:.72rem;color:#64748b;">
                                    {{ \Carbon\Carbon::parse($vc->start_time)->format('g:i A') }}
                                    &ndash;
                                    {{ \Carbon\Carbon::parse($vc->end_time)->format('g:i A') }}
                                </div>
                            </td>
                            <td>{{ $vc->providerLabel() }}</td>
                            <td><x-user-status-badge :status="$vc->status" /></td>
                            <td>{{ $vc->attendees->count() }}</td>
                            <td style="white-space:nowrap;">
                                @if($vc->class)
                                    <a href="{{ route('instructor.classes.virtual_classes.show', [$vc->class, $vc]) }}" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:20px;color:#64748b;">
                                No virtual classes scheduled.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($virtualClasses->hasPages())
            <div style="padding:14px 24px;">{{ $virtualClasses->links() }}</div>
        @endif
    </div>
@endsection
