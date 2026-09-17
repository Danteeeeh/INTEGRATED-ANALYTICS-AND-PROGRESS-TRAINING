@extends('layouts.admin')

@section('title', 'Courses')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'courses'])
@endsection

@section('content')
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        Courses
    </h2>
    <div class="page-actions">
        @can('courses.create')
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Add Course
            </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('admin.courses.index') }}" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search courses..." value="{{ request('search') }}" class="form-control">
                </div>
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body">
        @if($courses->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Classes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td><strong>{{ $course->code }}</strong></td>
                                <td>
                                    <div>{{ $course->title }}</div>
                                    <small class="text-muted">{{ Str::limit($course->description, 50) }}</small>
                                </td>
                                <td>{{ $course->category?->name ?? '-' }}</td>
                                <td>{{ $course->duration_weeks ? $course->duration_weeks . ' weeks' : '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $course->status }}">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td>{{ $course->classes()->count() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        @can('courses.view')
                                            <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-sm btn-icon" title="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('courses.update')
                                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-icon" title="Edit">
                                                <i class="fa-solid fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('courses.publish')
                                            @if($course->status === 'draft')
                                                <form method="POST" action="{{ route('admin.courses.publish', $course) }}" class="inline-form">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-success" title="Publish" onclick="return confirm('Are you sure you want to publish this course?')">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('courses.archive')
                                            @if($course->status === 'published')
                                                <form method="POST" action="{{ route('admin.courses.archive', $course) }}" class="inline-form">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-warning" title="Archive" onclick="return confirm('Are you sure you want to archive this course?')">
                                                        <i class="fa-solid fa-archive"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('courses.duplicate')
                                            <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}" class="inline-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-icon" title="Duplicate" onclick="return confirm('Are you sure you want to duplicate this course?')">
                                                    <i class="fa-solid fa-copy"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($courses->hasPages())
                <div class="pagination">
                    {{ $courses->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fa-solid fa-book"></i>
                <h3>No courses found</h3>
                <p>Get started by adding your first course.</p>
                @can('courses.create')
                    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Add Course
                    </a>
                @endcan
            </div>
        @endif
    </div>
</div>

@push('scripts')
<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-draft { background: #95a5a6; color: white; }
.badge-published { background: #2ecc71; color: white; }
.badge-archived { background: #e74c3c; color: white; }

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f5f5f5;
    color: #333;
}

.btn-success:hover {
    background: #efe;
    color: #2ecc71;
    border-color: #2ecc71;
}

.btn-warning:hover {
    background: #ffe;
    color: #f39c12;
    border-color: #f39c12;
}

.inline-form {
    display: inline;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.empty-state p {
    margin: 0 0 20px 0;
}

.text-muted {
    color: #666;
    font-size: 12px;
}
</style>
@endpush
@endsection