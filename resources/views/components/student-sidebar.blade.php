<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">Student Portal</span></div></div>
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
            <button type="button" class="nav-group-label" data-sidebar-group="learning" aria-expanded="true">Learning</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'modules' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'modules') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Modules & Lessons</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('student.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>My Classes</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>My Grades</span>
                        <span class="link-hint">Pick class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                        <span class="link-hint">Pick class</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="courses" aria-expanded="true">In your courses</button>
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
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index', ['feature' => 'announcements']) }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Announcements</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assistant" aria-expanded="true">Assist</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'assistant' ? 'active' : '' }}">
                    <a href="{{ route('student.assistant.index') }}" class="nav-link" @if($activeNav === 'assistant') aria-current="page" @endif>
                        <i class="fa-solid fa-robot"></i>
                        <span>Study Assistant</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="learning" aria-expanded="true">Learning</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'progress' ? 'active' : '' }}">
                    <a href="{{ route('student.progress') }}" class="nav-link" @if($activeNav === 'progress') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>My Progress</span>
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
    </nav>
</aside>
