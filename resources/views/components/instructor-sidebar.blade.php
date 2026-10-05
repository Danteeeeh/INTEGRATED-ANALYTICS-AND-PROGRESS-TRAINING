<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">Instructor Panel</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('instructor.dashboard') }}" class="nav-link" @if($activeNav === 'dashboard') aria-current="page" @endif>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academics" aria-expanded="true">Academic Management</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>Classes</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('instructor.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="content" aria-expanded="true">Course Content</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'modules' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'modules']) }}" class="nav-link" @if($activeNav === 'modules') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Modules</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'lessons' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'lessons']) }}" class="nav-link" @if($activeNav === 'lessons') aria-current="page" @endif>
                        <i class="fa-solid fa-book-open-reader"></i>
                        <span>Lessons</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessments" aria-expanded="true">Assessments</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'assignments' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'assignments']) }}" class="nav-link" @if($activeNav === 'assignments') aria-current="page" @endif>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'quizzes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'quizzes']) }}" class="nav-link" @if($activeNav === 'quizzes') aria-current="page" @endif>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'exams' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'exams']) }}" class="nav-link" @if($activeNav === 'exams') aria-current="page" @endif>
                        <i class="fa-solid fa-file-signature"></i>
                        <span>Exams</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'rubrics' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'rubrics']) }}" class="nav-link" @if($activeNav === 'rubrics') aria-current="page" @endif>
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rubrics</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="classroom" aria-expanded="true">Classroom</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'virtual_classes']) }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'announcements']) }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Announcements</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'attendance' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'attendance']) }}" class="nav-link" @if($activeNav === 'attendance') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Attendance</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'calendar' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'calendar']) }}" class="nav-link" @if($activeNav === 'calendar') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar"></i>
                        <span>Calendar</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="evaluation" aria-expanded="true">Evaluation</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'gradebook']) }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Gradebook</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'learning_plans' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'learning_plans']) }}" class="nav-link" @if($activeNav === 'learning_plans') aria-current="page" @endif>
                        <i class="fa-solid fa-route"></i>
                        <span>Learning Plans</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>