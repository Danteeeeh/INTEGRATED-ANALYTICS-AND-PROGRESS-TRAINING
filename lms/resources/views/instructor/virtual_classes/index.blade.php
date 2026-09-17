@extends('layouts.instructor')

@section('title', 'Virtual Classes - ' . $class->name)
@php
    $activeNav = 'classes';
    $pageTitle = 'Virtual Classes';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Virtual Classes &mdash; {{ $class->code }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>{{ $class->name }} &mdash; Virtual Classes</h3>
            <a href="{{ route('instructor.classes.virtual_classes.create', $class) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Virtual Class
            </a>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date/Time</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Attendees</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($virtualClasses as $vc)
                    <tr>
                        <td>{{ $vc->title }}</td>
                        <td>
                            {{ $vc->meeting_date->format('M d, Y') }}
                            <br>
                            <small style="color:#64748b;">{{ \Carbon\Carbon::parse($vc->start_time)->format('g:i A') }} &ndash; {{ \Carbon\Carbon::parse($vc->end_time)->format('g:i A') }}</small>
                        </td>
                        <td>
                            @switch($vc->meeting_provider)
                                @case('zoom')
                                    <i class="fa-solid fa-video" style="color:#2D8CFF;"></i> Zoom
                                    @break
                                @case('google_meet')
                                    <i class="fa-solid fa-video" style="color:#EA4335;"></i> Google Meet
                                    @break
                                @case('microsoft_teams')
                                    <i class="fa-solid fa-video" style="color:#6264A7;"></i> MS Teams
                                    @break
                                @default
                                    <i class="fa-solid fa-video"></i> Other
                            @endswitch
                        </td>
                        <td>
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                                {{ $vc->status === 'ongoing' ? 'background: #dcfce7; color: #16a34a;' :
                                   $vc->status === 'scheduled' ? 'background: #dbeafe; color: #1e40af;' :
                                   $vc->status === 'completed' ? 'background: #f1f5f9; color: #64748b;' :
                                   'background: #fee2e2; color: #dc2626;' }}">
                                {{ ucfirst($vc->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $vc->attendees->count() }}
                            @if($class->enrollments && $class->enrollments->count() > 0)
                                / {{ $class->enrollments->count() }}
                            @endif
                        </td>
                        <td class="actions-cell">
                            @if($vc->status === 'scheduled')
                                <form method="POST" action="{{ route('instructor.classes.virtual_classes.start', [$class, $vc]) }}" style="display:inline;" onsubmit="return confirm('Start this virtual class?');">
                                    @csrf
                                    <button type="submit" class="btn-icon btn-edit" title="Start">
                                        <i class="fa-solid fa-play"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('instructor.classes.virtual_classes.show', [$class, $vc]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No virtual classes scheduled yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($virtualClasses->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid var(--border-color, #e2e8f0);">
                {{ $virtualClasses->links() }}
            </div>
        @endif
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="{{ route('instructor.classes.show', $class) }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            &larr; Back to Class
        </a>
    </div>
@endsection
