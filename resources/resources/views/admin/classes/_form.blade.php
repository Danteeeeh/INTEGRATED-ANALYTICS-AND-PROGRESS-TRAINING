@php
    $editing = isset($class) && $class;
    $classValue = fn (string $field, $fallback = '') => old($field, $editing ? data_get($class, $field, $fallback) : $fallback);
    $formAction = $editing ? route('admin.classes.update', $class) : route('admin.classes.store');
    $submitLabel = $editing ? 'Save Changes' : 'Create Class';
@endphp

<style>
    .class-form-shell { max-width: 800px; margin: 0 auto; }
    .class-hero { display:flex; align-items:center; gap:15px; margin-bottom:20px; padding:20px; border:1px solid #e0e0e0; border-radius:12px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .class-hero-icon { display:grid; place-items:center; width:50px; height:50px; flex:none; border-radius:12px; color:#fff; background:rgba(255,255,255,0.2); }
    .class-hero h3 { margin:0; color:#fff; font-size:1.1rem; }
    .class-hero p { margin:5px 0 0; color:rgba(255,255,255,0.8); font-size:0.85rem; }
    .wizard-steps { display:flex; justify-content:space-between; margin-bottom:25px; padding:0 10px; }
    .step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
    .step:not(:last-child)::after { content:''; position:absolute; top:20px; left:50%; width:100%; height:2px; background:#e0e0e0; z-index:0; }
    .step.active:not(:last-child)::after, .step.completed:not(:last-child)::after { background:#667eea; }
    .step-number { width:40px; height:40px; border-radius:50%; background:#e0e0e0; color:#666; display:flex; align-items:center; justify-content:center; font-weight:bold; margin-bottom:8px; z-index:1; transition:all 0.3s; }
    .step.active .step-number { background:#667eea; color:white; }
    .step.completed .step-number { background:#2ecc71; color:white; }
    .step-label { font-size:12px; color:#666; text-align:center; }
    .step.active .step-label { color:#667eea; font-weight:bold; }
    .wizard-step { display:none; }
    .wizard-step.active { display:block; }
    .step-description { color:#666; margin-bottom:20px; font-size:14px; }
    .form-card { background:white; border:1px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
    .form-card-body { padding:25px; }
    .form-group { margin-bottom:20px; }
    .form-group label { display:block; margin-bottom:8px; color:#333; font-weight:600; font-size:14px; }
    .form-group .required { color:#e74c3c; }
    .form-group input, .form-group select { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:14px; transition:border-color 0.3s; }
    .form-group input:focus, .form-group select:focus { outline:none; border-color:#667eea; }
    .form-group .help { color:#666; font-size:12px; margin-top:5px; }
    .form-group .error { color:#e74c3c; font-size:12px; margin-top:5px; }
    .wizard-buttons { display:flex; justify-content:space-between; margin-top:25px; }
    .review-summary { background:#f8f9fa; padding:20px; border-radius:8px; margin-bottom:20px; }
    .review-item { margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #e0e0e0; }
    .review-item:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
    .review-item strong { color:#333; display:inline-block; width:120px; }
    .review-item span { color:#666; }
    @media(max-width:680px) { .wizard-steps { padding:0 5px; } .step-label { font-size:10px; } .form-card-body { padding:15px; } .wizard-buttons { flex-direction:column-reverse; } .wizard-buttons .btn { width:100%; margin-bottom:10px; } }
</style>

<div class="class-form-shell">
    <section class="class-hero">
        <span class="class-hero-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
        <div>
            <h3>{{ $editing ? 'Update Class Details' : 'Create New Class' }}</h3>
            <p>{{ $editing ? 'Update the class schedule, instructor, and capacity.' : 'Set up a new class by connecting it to a course and instructor.' }}</p>
        </div>
    </section>

    <!-- Step Indicator -->
    <div class="wizard-steps">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Course & Code</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Instructor & Period</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label">Schedule & Room</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-label">Review</div>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ $formAction }}" id="classWizardForm">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <!-- Step 1: Course & Code -->
        <div class="wizard-step active" data-step="1">
            <div class="form-card-body">
                <h3><i class="fa-solid fa-book"></i> Step 1: Course & Class Code</h3>
                <p class="step-description">Choose which course this class is for and create a class code.</p>

                <div class="form-group">
                    <label for="course_id">Course <span class="required">*</span></label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select a course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string)$classValue('course_id') === (string)$course->id)>{{ $course->code }} — {{ $course->title }}</option>
                        @endforeach
                    </select>
                    <small class="help">Which course will this class teach?</small>
                    @error('course_id')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="section_id">Section (Optional)</label>
                    <select id="section_id" name="section_id">
                        <option value="">No Section</option>
                        @foreach($sections ?? [] as $section)
                            <option value="{{ $section->id }}" @selected((string)$classValue('section_id') === (string)$section->id)>{{ $section->name }} ({{ $section->code }})</option>
                        @endforeach
                    </select>
                    <small class="help">Select a section if applicable (e.g., 11001)</small>
                    @error('section_id')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="code">Class Code <span class="required">*</span></label>
                    <input id="code" type="text" name="code" value="{{ $classValue('code') }}" required maxlength="50" placeholder="e.g. CS101-11001">
                    <small class="help">Combine course code with section: CS101-11001</small>
                    @error('code')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="wizard-buttons">
                    <button type="button" class="btn btn-primary next-step">
                        Next <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Instructor & Period -->
        <div class="wizard-step" data-step="2">
            <div class="form-card-body">
                <h3><i class="fa-solid fa-user-tie"></i> Step 2: Instructor & Academic Period</h3>
                <p class="step-description">Assign an instructor and choose the academic period.</p>

                <div class="form-group">
                    <label for="instructor_id">Instructor <span class="required">*</span></label>
                    <select id="instructor_id" name="instructor_id" required>
                        <option value="">Select an instructor</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((string)$classValue('instructor_id') === (string)$instructor->id)>{{ $instructor->full_name }} ({{ $instructor->email }})</option>
                        @endforeach
                    </select>
                    <small class="help">Who will teach this class?</small>
                    @error('instructor_id')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="academic_period_id">Academic Period</label>
                    <select id="academic_period_id" name="academic_period_id">
                        <option value="">Select Academic Period</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected((string)$classValue('academic_period_id') === (string)$period->id)>{{ $period->name }} ({{ $period->code }})</option>
                        @endforeach
                    </select>
                    <small class="help">Which semester/term is this for?</small>
                    @error('academic_period_id')<span class="error">{{ $message }}</span>@enderror
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

        <!-- Step 3: Schedule & Room -->
        <div class="wizard-step" data-step="3">
            <div class="form-card-body">
                <h3><i class="fa-solid fa-calendar"></i> Step 3: Schedule & Room</h3>
                <p class="step-description">Set the class schedule and location.</p>

                <div class="form-group">
                    <label for="schedule">Schedule</label>
                    <input id="schedule" type="text" name="schedule" value="{{ $classValue('schedule') }}" maxlength="255" placeholder="e.g. Mon/Wed 09:00-10:30">
                    <small class="help">When will the class meet? (e.g., Mon/Wed 09:00-10:30)</small>
                    @error('schedule')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="room">Room or Location</label>
                    <input id="room" type="text" name="room" value="{{ $classValue('room') }}" maxlength="100" placeholder="e.g. Room 101, Building A">
                    <small class="help">Where will the class be held?</small>
                    @error('room')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="capacity">Maximum Students</label>
                    <input id="capacity" type="number" name="capacity" value="{{ $classValue('capacity', 30) }}" min="1" max="200" placeholder="30">
                    <small class="help">How many students can enroll? (Default: 30)</small>
                    @error('capacity')<span class="error">{{ $message }}</span>@enderror
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

        <!-- Step 4: Review -->
        <div class="wizard-step" data-step="4">
            <div class="form-card-body">
                <h3><i class="fa-solid fa-check-circle"></i> Step 4: Review & Create</h3>
                <p class="step-description">Review the class details before creating.</p>

                <div class="review-summary">
                    <div class="review-item">
                        <strong>Class Code:</strong> <span id="review-code">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Course:</strong> <span id="review-course">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Instructor:</strong> <span id="review-instructor">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Academic Period:</strong> <span id="review-period">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Schedule:</strong> <span id="review-schedule">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Room:</strong> <span id="review-room">-</span>
                    </div>
                    <div class="review-item">
                        <strong>Capacity:</strong> <span id="review-capacity">-</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active" @selected($classValue('status', 'active') === 'active')">Active (Ready for enrollment)</option>
                        <option value="inactive" @selected($classValue('status') === 'inactive')">Inactive (Not ready)</option>
                    </select>
                    <small class="help">Set to "Active" when ready for students to enroll.</small>
                    @error('status')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="wizard-buttons">
                    <button type="button" class="btn btn-secondary prev-step">
                        <i class="fa-solid fa-arrow-left"></i> Previous
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check"></i> {{ $submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
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
        const course = document.getElementById('course_id');
        const instructor = document.getElementById('instructor_id');
        const period = document.getElementById('academic_period_id');
        const schedule = document.getElementById('schedule').value || '-';
        const room = document.getElementById('room').value || '-';
        const capacity = document.getElementById('capacity').value || '-';

        document.getElementById('review-code').textContent = code;
        document.getElementById('review-course').textContent = course.options[course.selectedIndex]?.text || '-';
        document.getElementById('review-instructor').textContent = instructor.options[instructor.selectedIndex]?.text || '-';
        document.getElementById('review-period').textContent = period.options[period.selectedIndex]?.text || '-';
        document.getElementById('review-schedule').textContent = schedule;
        document.getElementById('review-room').textContent = room;
        document.getElementById('review-capacity').textContent = capacity;
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

    // Auto-generate class code based on course and section
    function generateClassCode() {
        const courseId = document.getElementById('course_id').value;
        const sectionId = document.getElementById('section_id').value;
        
        if (courseId) {
            const course = @json($courses);
            const selectedCourse = course.find(c => c.id == courseId);
            
            if (selectedCourse) {
                let code = selectedCourse.code;
                
                if (sectionId) {
                    const sections = @json($sections ?? []);
                    const selectedSection = sections.find(s => s.id == sectionId);
                    if (selectedSection) {
                        code += '-' + selectedSection.name;
                    }
                } else {
                    code += '-01';
                }
                
                document.getElementById('code').value = code;
            }
        }
    }

    document.getElementById('course_id').addEventListener('change', generateClassCode);
    document.getElementById('section_id').addEventListener('change', generateClassCode);

    // Initial review update
    updateReview();
});
</script>
@endpush
