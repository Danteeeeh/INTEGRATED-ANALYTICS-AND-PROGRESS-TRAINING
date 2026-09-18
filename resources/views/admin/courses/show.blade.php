@extends('layouts.admin')

@section('title', 'Course Details')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'courses'])
@endsection

@section('content')
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        {{ $course->title }}
    </h2>
    <div class="page-actions">
        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Courses
        </a>
        @can('courses.update')
            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-primary">
                <i class="fa-solid fa-edit"></i>
                Edit Course
            </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="course-profile">
            <div class="profile-header">
                <div class="course-thumbnail">
                    @if($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}">
                    @else
                        <div class="placeholder-thumbnail">
                            <i class="fa-solid fa-book"></i>
                        </div>
                    @endif
                </div>
                <div class="course-info">
                    <h2>{{ $course->title }}</h2>
                    <p class="course-code">{{ $course->code }}</p>
                    <div class="course-badges">
                        <span class="badge badge-{{ $course->status }}">
                            {{ ucfirst($course->status) }}
                        </span>
                        @if($course->category)
                            <span class="badge badge-category">
                                {{ $course->category->name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="course-details">
                <div class="detail-section">
                    <h3>Basic Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Course Code</label>
                            <span>{{ $course->code }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Title</label>
                            <span>{{ $course->title }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Category</label>
                            <span>{{ $course->category?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Duration</label>
                            <span>{{ $course->duration_weeks ? $course->duration_weeks . ' weeks' : '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Academic Period</label>
                            <span>{{ $course->academicPeriod?->name ?? '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span>{{ ucfirst($course->status) }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Description</h3>
                    <div class="detail-content">
                        {{ $course->description ?? 'No description provided.' }}
                    </div>
                </div>

                @if($course->objectives)
                    <div class="detail-section">
                        <h3>Learning Objectives</h3>
                        <div class="detail-content">
                            {{ $course->objectives }}
                        </div>
                    </div>
                @endif

                @if($course->prerequisites)
                    <div class="detail-section">
                        <h3>Prerequisites</h3>
                        <div class="detail-content">
                            {{ $course->prerequisites }}
                        </div>
                    </div>
                @endif

                <div class="detail-section">
                    <h3>Course Statistics</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Total Classes</label>
                            <span>{{ $course->classes()->count() }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Total Modules</label>
                            <span>{{ $course->modules()->count() }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Total Lessons</label>
                            <span>{{ $course->lessons()->count() }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Total Enrollments</label>
                            <span>{{ $course->enrollments()->count() }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        @can('courses.publish')
                            @if($course->status === 'draft')
                                <form method="POST" action="{{ route('admin.courses.publish', $course) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to publish this course?')">
                                        <i class="fa-solid fa-check"></i>
                                        Publish Course
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can('courses.archive')
                            @if($course->status === 'published')
                                <form method="POST" action="{{ route('admin.courses.archive', $course) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to archive this course?')">
                                        <i class="fa-solid fa-archive"></i>
                                        Archive Course
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can('courses.duplicate')
                            <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to duplicate this course?')">
                                    <i class="fa-solid fa-copy"></i>
                                    Duplicate Course
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Course Content</h3>
        <div class="card-actions">
            @can('modules.create')
                <a href="{{ route('admin.courses.modules.create', $course) }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Add Module
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        @if($course->modules()->count() > 0)
            <div class="modules-list">
                @foreach($course->modules()->orderBy('position')->get() as $module)
                    <div class="module-item">
                        <div class="module-header">
                            <h4>{{ $module->title }}</h4>
                            <div class="module-stats">
                                <span>{{ $module->lessons()->count() }} lessons</span>
                            </div>
                        </div>
                        <p>{{ Str::limit($module->description, 100) }}</p>
                        <div class="module-actions">
                            @can('modules.view')
                                <a href="{{ route('admin.courses.modules.show', [$course, $module]) }}" class="btn btn-sm btn-secondary">
                                    <i class="fa-solid fa-eye"></i>
                                    View
                                </a>
                            @endcan
                            @can('modules.update')
                                <a href="{{ route('admin.courses.modules.edit', [$course, $module]) }}" class="btn btn-sm btn-secondary">
                                    <i class="fa-solid fa-edit"></i>
                                    Edit
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-layer-group"></i>
                <h3>No modules yet</h3>
                <p>Start building your course content by adding modules.</p>
                @can('modules.create')
                    <a href="{{ route('admin.courses.modules.create', $course) }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Add Module
                    </a>
                @endcan
            </div>
        @endif
    </div>
</div>

@push('scripts')
<style>
.course-profile {
    max-width: 900px;
    margin: 0 auto;
}

.profile-header {
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.course-thumbnail {
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5f5f5;
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.placeholder-thumbnail {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 32px;
}

.course-info h2 {
    margin: 0 0 5px 0;
    color: #333;
}

.course-code {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.course-badges {
    display: flex;
    gap: 10px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-draft { background: #95a5a6; color: white; }
.badge-published { background: #2ecc71; color: white; }
.badge-archived { background: #e74c3c; color: white; }
.badge-category { background: #3498db; color: white; }

.detail-section {
    margin-bottom: 30px;
}

.detail-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.detail-item span {
    font-size: 14px;
    color: #333;
}

.detail-content {
    line-height: 1.6;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
}

.btn-secondary:hover {
    background: #e5e5e5;
}

.btn-success {
    background: #2ecc71;
    color: white;
}

.btn-success:hover {
    background: #27ae60;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.inline-form {
    display: inline;
}

.modules-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.module-item {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    background: #fafafa;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.module-header h4 {
    margin: 0;
    color: #333;
}

.module-stats {
    font-size: 12px;
    color: #666;
}

.module-item p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 14px;
}

.module-actions {
    display: flex;
    gap: 10px;
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
</style>
@endpush
@endsection