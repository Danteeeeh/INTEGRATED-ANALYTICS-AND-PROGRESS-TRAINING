@extends('layouts.admin')

@section('title', 'Academic Records')

@php
    $activeNav = 'gradebook';
    $pageTitle = 'Academic Records';
    $pageIcon = '<i class="fa-solid fa-file-lines"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-lines"></i>
            Academic Records
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3><i class="fa-solid fa-graduation-cap"></i> Produce an Academic Record</h3>

        <p style="margin:8px 0 18px;font-size:.83rem;color:#64748b;">
            Pick a student to generate the transcript you hand to the registrar's office.
            Only subjects whose grade items are released and graded are computed, using the same
            formula as the gradebooks.
        </p>

        <form method="GET" action="{{ route('admin.academic-records.index') }}"
              style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:20px;align-items:end;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">
                    Search student
                </label>
                <input type="search" name="search" value="{{ $search }}"
                       placeholder="Name, student no, or email"
                       style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">
                    Academic period
                </label>
                <select name="period_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                    <option value="">All periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected((int) $periodId === $period->id)>{{ $period->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-add" style="padding:10px 18px;border-radius:8px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="{{ route('admin.academic-records.index') }}" class="btn-add"
                   style="padding:10px 16px;border-radius:8px;background:#94a3b8;">Clear</a>
            </div>
        </form>

        <div style="overflow-x:auto;">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student No</th>
                        <th>Program</th>
                        <th>Section</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->full_name }}</td>
                            <td class="mono">{{ $student->identifier ?? '—' }}</td>
                            <td>{{ $student->program?->name ?? '—' }}</td>
                            <td>{{ $student->section?->code ?? '—' }}</td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="{{ route('admin.academic-records.show', ['student' => $student, 'period_id' => $periodId]) }}"
                                       class="btn-add" style="padding:7px 12px;border-radius:7px;">
                                        <i class="fa-solid fa-file-lines"></i> Produce Record
                                    </a>
                                    <a href="{{ route('admin.academic-records.export', ['student' => $student, 'period_id' => $periodId]) }}"
                                       class="btn-add"
                                       style="padding:7px 12px;border-radius:7px;background:#059669;">
                                        <i class="fa-solid fa-download"></i> CSV
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:32px;color:#94a3b8;">
                                <i class="fa-solid fa-user-slash" style="font-size:1.6rem;display:block;margin-bottom:8px;"></i>
                                No students found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="pagination">{{ $students->links() }}</div>
        @endif
    </div>
@endsection