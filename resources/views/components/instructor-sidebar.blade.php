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
            <button type="button" class="nav-group-label" data-sidebar-group="teaching" aria-expanded="true">Teaching</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>My Classes</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'enrollment' ? 'active' : '' }}">
                    <a href="{{ route('instructor.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollment') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'gradebook']) }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Gradebook</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="courses" aria-expanded="true">In your courses</button>
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

                <li class="nav-item {{ $activeNav === 'discussions' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'discussions']) }}" class="nav-link" @if($activeNav === 'discussions') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Discussions</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'announcements']) }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Announcements</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="classes" aria-expanded="true">In your classes</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'attendance' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'attendance']) }}" class="nav-link" @if($activeNav === 'attendance') aria-current="page" @endif>
                        <i class="fa-solid fa-clipboard-user"></i>
                        <span>Attendance</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'virtual_classes']) }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
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
    </nav>
</aside>