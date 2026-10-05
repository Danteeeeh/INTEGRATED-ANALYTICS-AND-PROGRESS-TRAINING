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
            <button type="button" class="nav-group-label" data-sidebar-group="lms" aria-expanded="true">LMS Features</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>Class Portal</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'lessons' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'lessons']) }}" class="nav-link" @if($activeNav === 'lessons') aria-current="page" @endif>
                        <i class="fa-solid fa-book-open-reader"></i>
                        <span>Lesson Material Upload</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'assignments' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'assignments']) }}" class="nav-link" @if($activeNav === 'assignments') aria-current="page" @endif>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignment Submission</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'quizzes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'quizzes']) }}" class="nav-link" @if($activeNav === 'quizzes') aria-current="page" @endif>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Online Quizzes</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'virtual_classes']) }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Class Link Integration</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('instructor.classes.index', ['feature' => 'gradebook']) }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Grading Integration</span>
                        <span class="link-hint">Choose class</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'announcements']) }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Feedback & Comments</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'modules' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index', ['feature' => 'modules']) }}" class="nav-link" @if($activeNav === 'modules') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Module Completion Tracking</span>
                        <span class="link-hint">Choose course</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('instructor.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>Multimedia Support</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'analytics' ? 'active' : '' }}">
                    <a href="{{ route('instructor.dashboard') }}" class="nav-link" @if($activeNav === 'analytics') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>LMS Analytics</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>