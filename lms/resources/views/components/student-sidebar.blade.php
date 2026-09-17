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

                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('student.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('student.classes.index') }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>My Grades</span>
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
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'assignments') aria-current="page" @endif>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'quizzes' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'quizzes') aria-current="page" @endif>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>

                <li class="nav-item {{ $activeNav === 'discussions' ? 'active' : '' }}">
                    <a href="{{ route('student.courses.index') }}" class="nav-link" @if($activeNav === 'discussions') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Discussions</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="coming-soon" aria-expanded="true">Coming soon</button>
            <ul class="nav-list">
                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>My Progress</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-certificate"></i>
                        <span>Certificates</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-medal"></i>
                        <span>Badges</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-calendar"></i>
                        <span>Calendar</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</aside>