@extends('layouts.admin')

@section('title', 'Edit Course')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'courses'])
@endsection

@section('content')
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        Edit Course
    </h2>
    <div class="page-actions">
        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Courses
        </a>
        @can('courses.view')
            <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-secondary">
                <i class="fa-solid fa-eye"></i>
                View Course
            </a>
        @endcan
    </div>
</div>

<div class="card course-form-card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}">
            @csrf
            @method('PUT')

            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Course Code <span class="required">*</span></label>
                        <input type="text" id="code" name="code" value="{{ old('code', $course->code) }}" required class="form-control" placeholder="e.g. CS101">
                        @error('code')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="title">Course Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required class="form-control" placeholder="e.g. Introduction to Computer Science">
                        @error('title')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $course->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="academic_period_id">Academic Period</label>
                        <select id="academic_period_id" name="academic_period_id" class="form-control">
                            <option value="">Select Period</option>
                            @foreach($academicPeriods ?? [] as $period)
                                <option value="{{ $period->id }}" {{ old('academic_period_id', $course->academic_period_id) == $period->id ? 'selected' : '' }}>
                                    {{ $period->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_period_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_weeks">Duration (Weeks)</label>
                        <input type="number" id="duration_weeks" name="duration_weeks" value="{{ old('duration_weeks', $course->duration_weeks) }}" class="form-control" placeholder="e.g. 12">
                        @error('duration_weeks')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required class="form-control">
                            <option value="draft" {{ old('status', $course->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $course->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status', $course->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Course Details</h3>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control" placeholder="Provide a detailed description of the course...">{{ old('description', $course->description) }}</textarea>
                    @error('description')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="objectives">Learning Objectives</label>
                    <textarea id="objectives" name="objectives" rows="3" class="form-control" placeholder="What will students learn in this course?">{{ old('objectives', $course->objectives) }}</textarea>
                    @error('objectives')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="prerequisites">Prerequisites</label>
                    <textarea id="prerequisites" name="prerequisites" rows="2" class="form-control" placeholder="Any required prior knowledge or courses...">{{ old('prerequisites', $course->prerequisites) }}</textarea>
                    @error('prerequisites')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="syllabus">Syllabus</label>
                    <textarea id="syllabus" name="syllabus" rows="4" class="form-control" placeholder="Course syllabus and schedule...">{{ old('syllabus', $course->syllabus) }}</textarea>
                    @error('syllabus')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i>
                    Update Course
                </button>
            </div>
        </form>
    </div>
</div>

@endsection