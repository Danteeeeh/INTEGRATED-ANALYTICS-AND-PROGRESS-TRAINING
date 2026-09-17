@extends('layouts.admin')

@section('title', 'Academic Periods')
@php
    $activeNav = 'academic_periods';
    $pageTitle = 'Academic Periods';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            Academic Periods
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Academic Periods</h3>
            <a href="{{ route('admin.academic_periods.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Period
            </a>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                    <tr>
                        <td>{{ $period->name }}</td>
                        <td>{{ $period->start_date->format('M d, Y') }}</td>
                        <td>{{ $period->end_date->format('M d, Y') }}</td>
                        <td>
                            <span class="badge {{ $period->is_current ? 'badge-active' : 'badge-inactive' }}">
                                {{ $period->is_current ? 'Current' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="actions-cell action-buttons">
                            @if(! $period->is_current)
                                <form method="POST" action="{{ route('admin.academic_periods.set-current', $period) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn-icon btn-success" title="Set as current" onclick="return confirm('Set this as the current academic period?')">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.academic_periods.show', $period) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.academic_periods.edit', $period) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.academic_periods.destroy', $period) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No academic periods found. <a href="{{ route('admin.academic_periods.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection