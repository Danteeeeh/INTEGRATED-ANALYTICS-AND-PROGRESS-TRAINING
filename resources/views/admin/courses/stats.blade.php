@extends('layouts.admin')

@section('title', 'Course Statistics - ' . $course->code)

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'courses'])
@endsection

@section('content')
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-chart-bar"></i>
        Course Statistics: {{ $course->code }} - {{ $course->title }}
    </h2>
    <div class="page-actions">
        <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Course
        </a>
        <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-primary">
            <i class="fa-solid fa-edit"></i>
            Edit Course
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_classes'] }}</div>
                    <div class="stat-label">Total Classes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['active_classes'] }}</div>
                    <div class="stat-label">Active Classes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_enrollments'] }}</div>
                    <div class="stat-label">Total Enrollments</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['active_enrollments'] }}</div>
                    <div class="stat-label">Active Enrollments</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_modules'] }}</div>
                    <div class="stat-label">Total Modules</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_lessons'] }}</div>
                    <div class="stat-label">Total Lessons</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fa-solid fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_assignments'] }}</div>
                    <div class="stat-label">Total Assignments</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fa-solid fa-question-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $stats['total_quizzes'] }}</div>
                    <div class="stat-label">Total Quizzes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fa-solid fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ number_format($stats['completion_rate'], 1) }}%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #666;
}

.stat-icon.success {
    background: #d4edda;
    color: #155724;
}

.stat-icon.info {
    background: #d1ecf1;
    color: #0c5460;
}

.stat-icon.warning {
    background: #fff3cd;
    color: #856404;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}
</style>
@endpush
@endsection