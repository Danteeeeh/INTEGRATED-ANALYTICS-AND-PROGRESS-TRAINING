<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">{{ auth()->user()->role?->name ?? 'Student' }} Portal</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('student.dashboard') }}" class="nav-link" @if($activeNav === 'dashboard') aria-current="page" @endif>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="learning" aria-expanded="true">My Learning</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>My Classes</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('student.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-file-contract"></i>
                        <span>My Enrollments</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="content" aria-expanded="true">Course Content</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'modules' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'modules') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Modules</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'lessons' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'lessons']) }}" class="nav-link" @if($activeNav === 'lessons') aria-current="page" @endif>
                        <i class="fa-solid fa-book-open-reader"></i>
                        <span>Lessons</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessments" aria-expanded="true">Assessments</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'assignments' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'assignments']) }}" class="nav-link" @if($activeNav === 'assignments') aria-current="page" @endif>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'quizzes' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'quizzes']) }}" class="nav-link" @if($activeNav === 'quizzes') aria-current="page" @endif>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'exams' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'exams']) }}" class="nav-link" @if($activeNav === 'exams') aria-current="page" @endif>
                        <i class="fa-solid fa-file-signature"></i>
                        <span>Exams</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="classroom" aria-expanded="true">Classroom</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                        <span class="link-hint">Pick class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'announcements']) }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Announcements</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'calendar' ? 'active' : '' }}">
                    <a href="{{ route('student.calendar') }}" class="nav-link" @if($activeNav === 'calendar') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="progress" aria-expanded="true">Progress</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'progress' ? 'active' : '' }}">
                    <a href="{{ route('student.progress') }}" class="nav-link" @if($activeNav === 'progress') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>My Progress</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>My Grades</span>
                        <span class="link-hint">Pick class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'learning_plans' ? 'active' : '' }}">
                    <a href="{{ route('student.learning-plans.index') }}" class="nav-link" @if($activeNav === 'learning_plans') aria-current="page" @endif>
                        <i class="fa-solid fa-route"></i>
                        <span>Learning Plans</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="ai" aria-expanded="true">AI Assistant</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'assistant' ? 'active' : '' }}">
                    <a href="{{ route('student.assistant.index') }}" class="nav-link" @if($activeNav === 'assistant') aria-current="page" @endif>
                        <i class="fa-solid fa-robot"></i>
                        <span>AI Tutor</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
