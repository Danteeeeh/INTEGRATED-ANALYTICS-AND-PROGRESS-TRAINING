@extends('layouts.instructor')
@section('title', 'Learning Plans — '.$class->code)
@php $activeNav = 'learning_plans'; @endphp
@section('page-title-bar')
<div class="page-title-bar"><h2 class="page-title"><i class="fa-solid fa-route"></i> Learning Plans — {{ $class->code }}</h2><div class="page-actions"><a href="{{ route('instructor.classes.show',$class) }}" class="btn-modal-cancel">Back to Class</a></div></div>
@endsection
@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3>Students &amp; Plan Status</h3>
        <span style="font-size:0.85rem;color:#64748b">{{ $class->course?->title ?? '' }} · {{ $class->section?->name ?? '' }}</span>
    </div>
    <table class="crud-table">
        <thead>
            <tr><th>Student</th><th>Risk</th><th>Plan</th><th>Items</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($students as $row)
                @php $plan = $row['plan']; @endphp
                <tr>
                    <td>{{ $row['student']?->full_name ?? '—' }}<br><small style="color:#64748b">{{ $row['student']?->email ?? '' }}</small></td>
                    <td>
                        @if($row['is_at_risk'])
                            <span class="badge" style="background:#fee2e2;color:#b91c1c">At risk</span>
                        @else
                            <span class="badge" style="background:#dcfce7;color:#166534">On track</span>
                        @endif
                    </td>
                    <td>{{ $plan?->title ?? '—' }}</td>
                    <td>{{ $plan?->items->count() ?? '—' }}</td>
                    <td>{{ $plan ? ucfirst($plan->status) : '—' }}</td>
                    <td>
                        @if($plan)
                            <span style="color:#16a34a;font-size:0.85rem"><i class="fa-solid fa-check"></i> Plan ready</span>
                        @else
                            <form method="POST" action="{{ route('instructor.classes.learning-plans.store', [$class, $row['student']]) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="btn-add" style="padding:6px 14px"><i class="fa-solid fa-wand-magic-sparkles"></i> Suggest plan</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:24px">No active students in this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
