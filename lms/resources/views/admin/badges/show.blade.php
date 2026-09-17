@extends('layouts.admin')

@section('title', $badge->name)
@php $activeNav = 'badges'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $badge->name }}"
        subtitle="Badge details, criteria, and awards."
        icon="fa-medal"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $badge->status }}" />
            <span class="user-status">{{ ucfirst($badge->badge_type) }}</span>
            <span class="user-status active">{{ $badge->awards->count() }} awarded</span>
        </x-slot>
        <x-slot name="actions">
            @can('update', $badge)
                <a href="{{ route('admin.badges.edit', $badge) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            @endcan
            <a href="{{ route('admin.badges.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Badge Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Type</label>
                    <div>{{ ucfirst($badge->badge_type) }}</div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $badge->course?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $badge->class?->code ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $badge->status }}" /></div>
                </div>
                @if($badge->criteria_description)
                    <div class="form-field full">
                        <label>Criteria</label>
                        <div>{{ $badge->criteria_description }}</div>
                    </div>
                @endif
                @if($badge->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $badge->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('award', $badge)
        @if($badge->status === 'active')
            <div class="user-panel">
                <div class="user-panel-head">
                    <h3><i class="fa-solid fa-award"></i> Award Badge</h3>
                </div>
                <div class="user-panel-body">
                    <form action="{{ route('admin.badges.award', $badge) }}" method="POST">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Student IDs <span class="required">*</span></label>
                                <input type="text" name="student_ids_list" required placeholder="e.g. 5, 12, 27">
                                <span class="field-error">{{ $errors->first('student_ids') }}</span>
                            </div>
                            <div class="form-field full">
                                <label>Award Note</label>
                                <textarea name="award_note" rows="2" placeholder="Optional note for the award"></textarea>
                            </div>
                        </div>
                        <div class="form-actions user-actions">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-award"></i> Award to Students</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-trophy"></i> Awards</h3>
            <span class="user-status">{{ $badge->awards->count() }} awarded</span>
        </div>
        <div class="user-panel-body">
            @if($badge->awards->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Issued By</th>
                                <th>Date</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($badge->awards as $award)
                                <tr>
                                    <td>{{ $award->student?->name ?? '—' }}</td>
                                    <td>{{ $award->awardedBy?->name ?? '—' }}</td>
                                    <td>{{ $award->issued_at?->format('M j, Y') ?? '—' }}</td>
                                    <td>{{ $award->award_reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-trophy"
                    title="No awards yet"
                    description="Award this badge to students to track recognition."
                />
            @endif
        </div>
    </div>

    @can('delete', $badge)
        <div class="user-actions">
            <form action="{{ route('admin.badges.destroy', $badge) }}" method="POST" onsubmit="return confirm('Delete this badge?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
        </div>
    @endcan
</div>
@endsection
