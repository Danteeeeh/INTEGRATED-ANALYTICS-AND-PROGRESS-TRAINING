@extends('layouts.admin')

@section('title', 'Notification Preferences')
@php $activeNav = 'notifications'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Notification Preferences"
        subtitle="Configure which channels and events notify each user."
        icon="fa-sliders"
    >
        <x-slot name="meta">
            <span class="user-status active">{{ $user->name }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.notifications.preferences') }}">
            <select class="form-control" name="user_id" aria-label="Select user">
                @foreach($users as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $user->id)>{{ $option->name }} ({{ $option->email }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user"></i> Load Preferences</button>
        </form>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-bell"></i> Delivery Channels</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.notifications.preferences.update') }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Notification channels for {{ $user->name }}</label>
                        <div class="checkbox-grid">
                            @foreach([
                                'email_notifications' => 'Email notifications',
                                'push_notifications' => 'Push notifications',
                                'sms_notifications' => 'SMS notifications',
                                'daily_digest' => 'Daily digest',
                                'weekly_summary' => 'Weekly summary',
                            ] as $key => $label)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="{{ $key }}" value="1" @checked($preferences[$key] ?? false)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-field full">
                        <label>Events to notify about</label>
                        <div class="checkbox-grid">
                            @foreach([
                                'notify_on_assignment' => 'Assignments',
                                'notify_on_quiz' => 'Quizzes',
                                'notify_on_grade' => 'Grades',
                                'notify_on_announcement' => 'Announcements',
                                'notify_on_discussion' => 'Discussions',
                                'notify_on_message' => 'Messages',
                                'notify_on_attendance' => 'Attendance',
                            ] as $key => $label)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="{{ $key }}" value="1" @checked($preferences[$key] ?? false)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Quiet hours start</label>
                        <input type="time" name="quiet_hours_start" value="{{ $preferences['quiet_hours_start'] ?? '' }}">
                    </div>

                    <div class="form-field">
                        <label>Quiet hours end</label>
                        <input type="time" name="quiet_hours_end" value="{{ $preferences['quiet_hours_end'] ?? '' }}">
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
