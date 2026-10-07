@extends('layouts.admin')

@section('title', 'Create Course')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'courses'])
@endsection

@section('content')
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        Create New Course
    </h2>
    <div class="page-actions">
        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Courses
        </a>
    </div>
</div>

<div class="wizard-container">
    <!-- Step Indicator -->
    <div class="wizard-steps">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Basic Info</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Department & Program</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label">Course Details</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-label">Review & Create</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.courses.store') }}" id="courseWizardForm">
        @csrf

        <!-- Step 1: Basic Information -->
        <div class="wizard-step active" data-step="1">
            <div class="card">
                <div class="card-body">
                    <h3><i class="fa-solid fa-info-circle"></i> Step 1: Basic Information</h3>
                    <p class="step-description">Let's start with the basic course details.</p>

                    <div class="form-group">
                        <label for="code">Course Code <span class="required">*</span></label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required class="form-control" placeholder="e.g. CS101, IT101, WEB101">
                        <small class="form-text">Use a short, unique code like CS101, IT101, etc.</small>
                        @error('code')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="title">Course Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required class="form-control" placeholder="e.g. Introduction to Computer Science">
                        <small class="form-text">Give your course a clear, descriptive title.</small>
                        @error('title')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="duration_weeks">Duration (Weeks)</label>
                        <input type="number" id="duration_weeks" name="duration_weeks" value="{{ old('duration_weeks', 12) }}" class="form-control" placeholder="e.g. 12">
                        <small class="form-text">How many weeks will this course run? (Typically 12-16 weeks)</small>
                        @error('duration_weeks')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="btn btn-primary next-step">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Department & Program -->
        <div class="wizard-step" data-step="2">
            <div class="card">
                <div class="card-body">
                    <h3><i class="fa-solid fa-building"></i> Step 2: Department & Program</h3>
                    <p class="step-description">Assign this course to a department and program.</p>

                    <div class="form-group">
                        <label for="academic_period_id">Academic Period</label>
                        <select id="academic_period_id" name="academic_period_id" class="form-control">
                            <option value="">Select Academic Period</option>
                            @foreach($academicPeriods ?? [] as $period)
                                <option value="{{ $period->id }}" {{ old('academic_period_id') == $period->id ? 'selected' : '' }}>
                                    {{ $period->name }} ({{ $period->code }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text">Choose the semester/term for this course.</small>
                        @error('academic_period_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="">Select Department</option>
                            @foreach($departments ?? [] as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }} ({{ $department->code }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text">Which department offers this course?</small>
                        @error('department_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="program_id">Program</label>
                        <select id="program_id" name="program_id" class="form-control">
                            <option value="">Select Program</option>
                            @foreach($programs ?? [] as $program)
                                <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                    {{ $program->code }} — {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text">Which program is this course part of?</small>
                        @error('program_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="btn btn-secondary prev-step">
                            <i class="fa-solid fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary next-step">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Course Details -->
        <div class="wizard-step" data-step="3">
            <div class="card">
                <div class="card-body">
                    <h3><i class="fa-solid fa-file-alt"></i> Step 3: Course Details</h3>
                    <p class="step-description">Add more details about the course content.</p>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" class="form-control" placeholder="Provide a detailed description of the course...">{{ old('description') }}</textarea>
                        <small class="form-text">Briefly describe what this course is about.</small>
                        @error('description')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="objectives">Learning Objectives</label>
                        <textarea id="objectives" name="objectives" rows="3" class="form-control" placeholder="What will students learn in this course?">{{ old('objectives') }}</textarea>
                        <small class="form-text">What skills and knowledge will students gain?</small>
                        @error('objectives')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="prerequisites">Prerequisites</label>
                        <textarea id="prerequisites" name="prerequisites" rows="2" class="form-control" placeholder="Any required prior knowledge or courses...">{{ old('prerequisites') }}</textarea>
                        <small class="form-text">Are there any courses students need to take first?</small>
                        @error('prerequisites')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="syllabus">Syllabus</label>
                        <textarea id="syllabus" name="syllabus" rows="4" class="form-control" placeholder="Course syllabus and schedule...">{{ old('syllabus') }}</textarea>
                        <small class="form-text">Optional: Add a detailed syllabus and weekly schedule.</small>
                        @error('syllabus')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="btn btn-secondary prev-step">
                            <i class="fa-solid fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary next-step">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Review & Create -->
        <div class="wizard-step" data-step="4">
            <div class="card">
                <div class="card-body">
                    <h3><i class="fa-solid fa-check-circle"></i> Step 4: Review & Create</h3>
                    <p class="step-description">Review your course details before creating.</p>

                    <div class="review-summary">
                        <div class="review-item">
                            <strong>Course Code:</strong> <span id="review-code">-</span>
                        </div>
                        <div class="review-item">
                            <strong>Title:</strong> <span id="review-title">-</span>
                        </div>
                        <div class="review-item">
                            <strong>Duration:</strong> <span id="review-duration">-</span> weeks
                        </div>
                        <div class="review-item">
                            <strong>Department:</strong> <span id="review-department">-</span>
                        </div>
                        <div class="review-item">
                            <strong>Program:</strong> <span id="review-program">-</span>
                        </div>
                        <div class="review-item">
                            <strong>Academic Period:</strong> <span id="review-period">-</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required class="form-control">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft (Not visible to students)</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published (Visible to students)</option>
                        </select>
                        <small class="form-text">Start with "Draft" if you're still preparing the course.</small>
                        @error('status')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="btn btn-secondary prev-step">
                            <i class="fa-solid fa-arrow-left"></i> Previous
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-check"></i> Create Course
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<style>
.wizard-container {
    max-width: 800px;
    margin: 0 auto;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    padding: 0 20px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e0e0e0;
    z-index: 0;
}

.step.active:not(:last-child)::after,
.step.completed:not(:last-child)::after {
    background: #3498db;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    z-index: 1;
    transition: all 0.3s;
}

.step.active .step-number {
    background: #3498db;
    color: white;
}

.step.completed .step-number {
    background: #2ecc71;
    color: white;
}

.step-label {
    font-size: 12px;
    color: #666;
    text-align: center;
}

.step.active .step-label {
    color: #3498db;
    font-weight: bold;
}

.wizard-step {
    display: none;
}

.wizard-step.active {
    display: block;
}

.step-description {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.form-text {
    color: #666;
    font-size: 12px;
    margin-top: 4px;
}

.required {
    color: #e74c3c;
}

.wizard-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.review-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.review-item {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.review-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.review-item strong {
    color: #333;
    margin-right: 10px;
}

.review-item span {
    color: #666;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.wizard-step');
    const stepIndicators = document.querySelectorAll('.step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    let currentStep = 1;

    function showStep(stepNumber) {
        steps.forEach(step => {
            step.classList.remove('active');
            if (parseInt(step.dataset.step) === stepNumber) {
                step.classList.add('active');
            }
        });

        stepIndicators.forEach(indicator => {
            indicator.classList.remove('active', 'completed');
            const indicatorStep = parseInt(indicator.dataset.step);
            if (indicatorStep === stepNumber) {
                indicator.classList.add('active');
            } else if (indicatorStep < stepNumber) {
                indicator.classList.add('completed');
            }
        });

        currentStep = stepNumber;
        updateReview();
    }

    function updateReview() {
        const code = document.getElementById('code').value || '-';
        const title = document.getElementById('title').value || '-';
        const duration = document.getElementById('duration_weeks').value || '-';
        const department = document.getElementById('department_id');
        const program = document.getElementById('program_id');
        const period = document.getElementById('academic_period_id');

        document.getElementById('review-code').textContent = code;
        document.getElementById('review-title').textContent = title;
        document.getElementById('review-duration').textContent = duration;
        document.getElementById('review-department').textContent = department.options[department.selectedIndex]?.text || '-';
        document.getElementById('review-program').textContent = program.options[program.selectedIndex]?.text || '-';
        document.getElementById('review-period').textContent = period.options[period.selectedIndex]?.text || '-';
    }

    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (currentStep < steps.length) {
                showStep(currentStep + 1);
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
    });

    // Auto-fill department based on program
    document.getElementById('program_id').addEventListener('change', function() {
        const programId = this.value;
        if (programId) {
            const program = @json($programs);
            const selectedProgram = program.find(p => p.id == programId);
            if (selectedProgram && selectedProgram.department_id) {
                document.getElementById('department_id').value = selectedProgram.department_id;
            }
        }
    });

    // Initial review update
    updateReview();
});
</script>
@endpush
@endsection