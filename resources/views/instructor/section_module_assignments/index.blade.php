@extends('layouts.instructor')

@section('title', 'Section Modules')
@php
    $activeNav = 'modules';
    $pageTitle = 'Section Modules';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            Modules assigned to this section
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.classes.show', $class) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Class
            </a>
            <a href="{{ route('instructor.classes.section_module_assignments.create', $class) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Assign Module
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-layer-group"></i> Assigned Modules ({{ $sectionAssignments->total() }})</h3>
        </div>

        @if (session('success'))
            <div style="padding: 12px 16px; color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; border-radius: 9px; margin: 14px 16px;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div style="padding: 12px 16px; color:#92400e; background:#fffbeb; border:1px solid #fde68a; border-radius: 9px; margin: 14px 16px;">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Section</th>
                    <th>Assigned</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sectionAssignments as $assignment)
                    <tr>
                        <td>
                            {{ $assignment->module?->title ?? 'Module #'.$assignment->module_id }}
                        </td>
                        <td>{{ $assignment->section?->code ?? '—' }}</td>
                        <td>{{ $assignment->assigned_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="actions-cell">
                            <form method="POST"
                                  action="{{ route('instructor.classes.section_module_assignments.destroy', [$class, $assignment]) }}"
                                  onsubmit="return confirm('Remove this module from the section?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-edit" title="Remove" style="color:#dc2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:#aaa;">
                            No modules are assigned to this section yet.
                            <a href="{{ route('instructor.classes.section_module_assignments.create', $class) }}" style="color:#2563eb;text-decoration:underline;">Assign one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($sectionAssignments->hasPages())
            <div class="pagination" style="padding: 14px 16px;">
                {{ $sectionAssignments->links() }}
            </div>
        @endif
    </div>
@endsection