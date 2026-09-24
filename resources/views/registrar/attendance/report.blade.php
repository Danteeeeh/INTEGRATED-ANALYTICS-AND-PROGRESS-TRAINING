@extends('layouts.registrar')
@section('title', 'Attendance Report — ' . $class->code)
@php($activeNav = 'attendance')

@section('page-title-bar')
<div class="page-title-bar">
    <h2 class="page-title"><i class="fa-solid fa-clipboard-user"></i> Attendance Report — {{ $class->code }}</h2>
    <div class="page-actions">
        <a href="{{ route('registrar.attendance.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>
@endsection

@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-chart-column"></i> Summary — {{ $class->course?->code ?? '' }} {{ $class->course?->title ?? '' }} · {{ $class->instructor?->full_name ?? 'Unassigned' }}</h3>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;padding:16px">
        @foreach($summary as $label => $count)
            <div style="padding:14px;border:1px solid var(--bcp-line);border-radius:12px;text-align:center;background:rgba(77,143,240,.05)">
                <div style="font-size:1.6rem;font-weight:800;color:var(--bcp-ink)">{{ $count }}</div>
                <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--bcp-muted)">{{ ucfirst($label) }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="crud-card" style="margin-top:16px">
    <div class="crud-header">
        <h3><i class="fa-solid fa-list"></i> Records ({{ $records->count() }})</h3>
    </div>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Session</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->attendance_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $record->student?->full_name ?? '—' }}</td>
                    <td>{{ $record->session_title ?? '—' }}</td>
                    <td><span class="badge {{ $record->status === 'present' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($record->status) }}</span></td>
                    <td style="font-size:.74rem;color:var(--bcp-muted)">{{ $record->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No attendance records for this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
