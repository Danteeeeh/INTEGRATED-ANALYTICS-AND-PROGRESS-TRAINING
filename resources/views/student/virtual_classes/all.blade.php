@extends('layouts.student')

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
            <a href="{{ route('student.dashboard') }}" class="btn btn-secondary btn-sm">
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
            <h3>Virtual classes for your enrolled courses ({{ $virtualClasses->total() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            The full list across your classes. Open a row for details or to join a live session.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Title</th>
                        <th>Date / Time</th>
                        <th>Status</th>
                        <th>My Attendance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($virtualClasses as $vc)
                        @php
                            $myAtt = $vc->attendees->first();
                            $canJoin = $vc->studentCanJoin();
                        @endphp
                        <tr>
                            <td>{{ $vc->class?->code ?? '—' }}</td>
                            <td>
                                {{ $vc->title }}
                                @if($vc->class?->course?->title)
                                    <div style="font-size:.72rem;color:#64748b;">{{ $vc->class->course->title }}</div>
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
                            <td><x-user-status-badge :status="$vc->status" /></td>
                            <td>
                                @if($myAtt)
                                    {{ ucfirst($myAtt->attendance_status) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if($vc->class)
                                    <a href="{{ route('student.classes.virtual_classes.show', [$vc->class, $vc]) }}" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    @if($canJoin)
                                        <form action="{{ route('student.classes.virtual_classes.join', [$vc->class, $vc]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Join this virtual class? Your attendance will be recorded.');">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-right-to-bracket"></i> Join now
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
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
