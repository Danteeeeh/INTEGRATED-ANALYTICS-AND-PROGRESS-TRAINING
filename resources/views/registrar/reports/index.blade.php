@extends('layouts.registrar')
@section('title', 'Academic Reports')
@php $activeNav = 'reports'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Academic Reports"
        subtitle="Key enrollment and class metrics at a glance."
        icon="fa-chart-bar"
    />

    <div class="user-stat-grid">
        <x-user-stat-card label="Students" value="{{ $stats['total_students'] ?? 0 }}" icon="fa-user-graduate" />
        <x-user-stat-card label="Active Enrollments" value="{{ $stats['active_enrollments'] ?? 0 }}" icon="fa-user-plus" />
        <x-user-stat-card label="Completed" value="{{ $stats['completed_enrollments'] ?? 0 }}" icon="fa-circle-check" />
        <x-user-stat-card label="Dropped" value="{{ $stats['dropped_enrollments'] ?? 0 }}" icon="fa-user-minus" />
        <x-user-stat-card label="Classes" value="{{ $stats['total_classes'] ?? 0 }}" icon="fa-school" />
        <x-user-stat-card label="Pending Enrollments" value="{{ $stats['pending_enrollments'] ?? 0 }}" icon="fa-clock" />
    </div>
</div>
@endsection
