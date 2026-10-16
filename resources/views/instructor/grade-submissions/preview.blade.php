@extends('layouts.instructor')

@php
    $activeNav = 'grade_submissions';
@endphp

@section('title', 'Preview Grade Export')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            Preview Grade Export — {{ $class->code }}
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.grade-submissions.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('instructor.grade-submissions.export', $class) }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;">
        <i class="fa-solid fa-circle-info" style="color:#fbbf24"></i>
        <span><strong>Preview only.</strong> Ang pag-export ng CSV ay hindi nagsu-submit. Ang official final grade ay nasa AA at hindi nababago ng workflow na ito.</span>
    </div>

    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Validation summary</h3>
        </div>
        <div style="padding:16px;display:flex;gap:26px;flex-wrap:wrap;font-size:.88rem;">
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Students</div><strong>{{ $summary['student_count'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Graded</div><strong>{{ $summary['graded_count'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Missing / incomplete</div>
                <strong style="color:{{ $summary['missing_count'] > 0 ? '#fbbf24' : '#34d399' }};">{{ $summary['missing_count'] }}</strong>
            </div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Average (LMS)</div>
                <strong>{{ $summary['average_percent'] !== null ? number_format((float) $summary['average_percent'], 2).'%' : '—' }}</strong>
            </div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Complete</div>
                <strong>{{ $summary['is_complete'] ? 'Yes' : 'No' }}</strong>
            </div>
        </div>
        @if($summary['missing_count'] > 0)
            <div style="padding:0 16px 16px;">
                <div style="padding:10px 14px;background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.35);border-radius:8px;font-size:.83rem;color:#fbbf24;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    May {{ $summary['missing_count'] }} student na walang graded assessment. I-flag muna ang mga ito bago i-submit kung kailangan ng kumpletong batch.
                </div>
            </div>
        @endif
    </div>

    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Extracted rows</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th style="text-align:center;">Graded items</th>
                    <th style="text-align:center;">LMS Score %</th>
                    <th style="text-align:center;">Letter</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['identifier'] ?? $row['student_id'] }}</td>
                        <td>{{ $row['name'] ?? '—' }}</td>
                        <td>{{ ucfirst($row['enrollment_status']) }}</td>
                        <td style="text-align:center;">{{ $row['graded_items'] }}</td>
                        <td style="text-align:center;">
                            @if($row['is_graded'])
                                {{ number_format((float) $row['lms_score'], 2) }}
                            @else
                                <span style="color:#fbbf24;font-weight:700;">Missing</span>
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $row['letter_grade'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
                            Walang active enrollment ang class na ito. Ang enrollment ay galing sa AA sync.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Submit grades</h3>
        </div>
        <form method="POST" action="{{ route('instructor.grade-submissions.store', $class) }}" style="padding:16px;">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="notes" style="display:block;font-size:.82rem;color:#94a3b8;margin-bottom:6px;">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000"
                    style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(148,163,184,.35);background:rgba(15,23,42,.35);color:inherit;font-size:.88rem;"
                    placeholder="Hal. Soft copy ng grades para sa Midterm, ipapasa sa AA kung hilingin.">{{ old('notes') }}</textarea>
                @error('notes')<div style="color:#f87171;font-size:.8rem;margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-sm"
                onclick="return confirm('I-record ang grade submission para sa {{ $class->code }}? Hindi nito babaguhin ang official AA records.');">
                <i class="fa-solid fa-paper-plane"></i> Submit Grades
            </button>
        </form>
    </div>

    @if($history->isNotEmpty())
        <div class="crud-card" style="margin-top:18px;">
            <div class="crud-header">
                <h3>Previous submissions for this class</h3>
            </div>
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Submitted</th>
                        <th style="text-align:center;">Students</th>
                        <th style="text-align:center;">Graded</th>
                        <th style="text-align:center;">Missing</th>
                        <th style="text-align:center;">Average</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $past)
                        <tr>
                            <td>{{ $past->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            <td style="text-align:center;">{{ $past->student_count }}</td>
                            <td style="text-align:center;">{{ $past->graded_count }}</td>
                            <td style="text-align:center;">{{ $past->missing_count }}</td>
                            <td style="text-align:center;">
                                {{ $past->average_percent !== null ? number_format((float) $past->average_percent, 2) : '—' }}
                            </td>
                            <td><a href="{{ route('instructor.grade-submissions.show', $past) }}" class="btn btn-secondary btn-sm">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
