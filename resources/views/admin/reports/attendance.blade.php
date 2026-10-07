@extends('layouts.admin')
@section('title', 'Attendance Report')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-calendar-check"></i> Attendance records</h3>
        <span class="dash-section-kicker">Every marked attendance entry across classes and periods</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.attendance') }}" id="attendanceFilterForm">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search attendance" id="attendanceSearch">

        <select name="class_id" aria-label="Filter class" onchange="this.form.submit()">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>
                    {{ $class->code }}@if($class->course) — {{ $class->course->title }}@endif
                </option>
            @endforeach
        </select>

        <select name="student_id" aria-label="Filter student" onchange="this.form.submit()">
            <option value="">All students</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                    {{ $student->first_name }} {{ $student->last_name }}
                </option>
            @endforeach
        </select>

        <select name="academic_period_id" aria-label="Filter academic period" onchange="this.form.submit()">
            <option value="">All periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>

        <input type="date" name="from_date" value="{{ request('from_date') }}" aria-label="From date">
        <input type="date" name="to_date" value="{{ request('to_date') }}" aria-label="To date">

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.attendance') }}">Clear</a>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('importAttendanceModal').classList.add('active')"><i class="fa-solid fa-file-import"></i> Import</button>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'attendance'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>

    <table class="dash-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th>Period</th>
                <th>Status</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Marked By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceRecords as $record)
                <tr>
                    <td>{{ $record->attendance_date?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        <strong>{{ $record->student?->first_name }} {{ $record->student?->last_name }}</strong>
                        <div class="dash-muted">{{ $record->student?->email }}</div>
                    </td>
                    <td>
                        {{ $record->class?->code ?? '—' }}
                        @if($record->class?->course)
                            <div class="dash-muted">{{ $record->class->course->title }}</div>
                        @endif
                    </td>
                    <td>{{ $record->class?->academicPeriod?->name ?? '—' }}</td>
                    <td>
                        @php($badge = match ($record->status) {
                            'present'   => ['ok', 'Present'],
                            'late'      => ['warn', 'Late'],
                            'absent'    => ['bad', 'Absent'],
                            'excused'   => ['info', 'Excused'],
                            default     => ['muted', ucfirst((string) $record->status)],
                        })
                        <span class="dash-badge dash-badge--{{ $badge[0] }}">{{ $badge[1] }}</span>
                    </td>
                    <td>{{ $record->time_in ?? '—' }}</td>
                    <td>{{ $record->time_out ?? '—' }}</td>
                    <td>{{ $record->markedBy?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No attendance records match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($attendanceRecords->hasPages())
        <div class="dash-pagination">
            {{ $attendanceRecords->links() }}
        </div>
    @endif
</section>

{{-- Bulk import --}}
<div class="modal" id="importAttendanceModal" role="dialog" aria-modal="true" aria-labelledby="importAttendanceTitle">
    <div class="modal__backdrop" onclick="document.getElementById('importAttendanceModal').classList.remove('active')"></div>
    <div class="modal__panel">
        <header class="modal__head">
            <h3 id="importAttendanceTitle">Import attendance</h3>
            <button type="button" class="modal__close" onclick="document.getElementById('importAttendanceModal').classList.remove('active')" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('admin.reports.attendance.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal__body">
                <label for="attendanceImportFile" class="form-label">CSV file</label>
                <input id="attendanceImportFile" type="file" name="file" accept=".csv,text/csv" required class="form-control">
                <p class="dash-muted" style="margin-top:10px">
                    Expected columns: student_identifier or email, class_code, attendance_date, status, time_in, time_out.
                </p>
                @error('file')
                    <p class="text-danger" style="margin-top:8px">{{ $message }}</p>
                @enderror
            </div>
            <footer class="modal__foot">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('importAttendanceModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Import</button>
            </footer>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Typing in the search box filters after a short pause, so the report does
    // not reload on every keystroke.
    (function () {
        var box = document.getElementById('attendanceSearch');
        var form = document.getElementById('attendanceFilterForm');
        if (!box || !form) return;

        var timer = null;
        box.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });
    })();
</script>
@endpush
@endsection
