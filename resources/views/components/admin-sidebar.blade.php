<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">{{ auth()->user()->role?->name ?? 'Admin' }} Panel</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link" @if($activeNav === 'dashboard') aria-current="page" @endif>
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
                    <a href="{{ route('admin.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'modules' ? 'active' : '' }}">
                    <a href="{{ route('admin.modules.index') }}" class="nav-link" @if($activeNav === 'modules') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Modules</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'lessons' ? 'active' : '' }}">
                    <a href="{{ route('admin.lessons.index') }}" class="nav-link" @if($activeNav === 'lessons') aria-current="page" @endif>
                        <i class="fa-solid fa-book-open-reader"></i>
                        <span>Lessons</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('admin.classes.index') }}" class="nav-link" @if($activeNav === 'classes') aria-current="page" @endif>
                        <i class="fa-solid fa-school"></i>
                        <span>Classes</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessments" aria-expanded="true">Assessments</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'assignments' ? 'active' : '' }}">
                    <a href="{{ route('admin.assignments.index') }}" class="nav-link" @if($activeNav === 'assignments') aria-current="page" @endif>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'quizzes' ? 'active' : '' }}">
                    <a href="{{ route('admin.quizzes.index') }}" class="nav-link" @if($activeNav === 'quizzes') aria-current="page" @endif>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'exams' ? 'active' : '' }}">
                    <a href="{{ route('admin.exams.index') }}" class="nav-link" @if($activeNav === 'exams') aria-current="page" @endif>
                        <i class="fa-solid fa-file-signature"></i>
                        <span>Exams</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'question_banks' ? 'active' : '' }}">
                    <a href="{{ route('admin.question_banks.index') }}" class="nav-link" @if($activeNav === 'question_banks') aria-current="page" @endif>
                        <i class="fa-solid fa-database"></i>
                        <span>Question Banks</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'rubrics' ? 'active' : '' }}">
                    <a href="{{ route('admin.rubrics.index') }}" class="nav-link" @if($activeNav === 'rubrics') aria-current="page" @endif>
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rubrics</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="classroom" aria-expanded="true">Classroom</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('admin.virtual_classes.index') }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('admin.announcements.index') }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-comments"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'attendance' ? 'active' : '' }}">
                    <a href="{{ route('admin.attendance.index') }}" class="nav-link" @if($activeNav === 'attendance') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="evaluation" aria-expanded="true">Evaluation</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('admin.gradebook.index') }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Gradebook</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'analytics' ? 'active' : '' }}">
                    <a href="{{ route('admin.analytics') }}" class="nav-link" @if($activeNav === 'analytics') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'reports' ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.index') }}" class="nav-link" @if($activeNav === 'reports') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="users" aria-expanded="true">User Management</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'users' ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="nav-link" @if($activeNav === 'users') aria-current="page" @endif>
                        <i class="fa-solid fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'students' ? 'active' : '' }}">
                    <a href="{{ route('admin.students.index') }}" class="nav-link" @if($activeNav === 'students') aria-current="page" @endif>
                        <i class="fa-solid fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'instructors' ? 'active' : '' }}">
                    <a href="{{ route('admin.instructors.index') }}" class="nav-link" @if($activeNav === 'instructors') aria-current="page" @endif>
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <span>Instructors</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('admin.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="organization" aria-expanded="true">Organization</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'departments' ? 'active' : '' }}">
                    <a href="{{ route('admin.departments.index') }}" class="nav-link" @if($activeNav === 'departments') aria-current="page" @endif>
                        <i class="fa-solid fa-building"></i>
                        <span>Departments</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'programs' ? 'active' : '' }}">
                    <a href="{{ route('admin.programs.index') }}" class="nav-link" @if($activeNav === 'programs') aria-current="page" @endif>
                        <i class="fa-solid fa-sitemap"></i>
                        <span>Programs</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'sections' ? 'active' : '' }}">
                    <a href="{{ route('admin.sections.index') }}" class="nav-link" @if($activeNav === 'sections') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Sections</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'academic_periods' ? 'active' : '' }}">
                    <a href="{{ route('admin.academic_periods.index') }}" class="nav-link" @if($activeNav === 'academic_periods') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Academic Periods</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'calendar' ? 'active' : '' }}">
                    <a href="{{ route('admin.calendar.index') }}" class="nav-link" @if($activeNav === 'calendar') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'competencies' ? 'active' : '' }}">
                    <a href="{{ route('admin.competencies.index') }}" class="nav-link" @if($activeNav === 'competencies') aria-current="page" @endif>
                        <i class="fa-solid fa-medal"></i>
                        <span>Competencies</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'audit_logs' ? 'active' : '' }}">
                    <a href="{{ route('admin.audit_logs.index') }}" class="nav-link" @if($activeNav === 'audit_logs') aria-current="page" @endif>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'backup' ? 'active' : '' }}">
                    <a href="{{ route('admin.backup.index') }}" class="nav-link" @if($activeNav === 'backup') aria-current="page" @endif>
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Backup</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'settings' ? 'active' : '' }}">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link nav-link--settings" title="System settings" @if($activeNav === 'settings') aria-current="page" @endif>
                        <i class="fa-solid fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
