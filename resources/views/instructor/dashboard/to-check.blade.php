@extends('layouts.instructor')

@section('title', 'To-check Queue')
@php
    $activeNav = 'dashboard';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-clipboard-check"></i>
            To-check Queue
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
            <h3>Submitted work awaiting grading ({{ $items->count() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            Submitted assignments and submitted/auto-submitted quiz attempts in your classes. Open an item to grade it.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item['kind'] }}</td>
                            <td>{{ $item['title'] }}</td>
                            <td>{{ $item['student'] }}</td>
                            <td>{{ $item['class_code'] ?: '—' }}</td>
                            <td>{{ $item['submitted_at'] ? \Illuminate\Support\Carbon::parse($item['submitted_at'])->format('M d, Y g:i A') : '—' }}</td>
                            <td style="white-space:nowrap;">
                                @if($item['url'])
                                    <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Grade
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
                                Nothing is waiting to be checked.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
