<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">Admin Panel</span></div></div>
    </div>

    @php
        $departments = \App\Models\Department::with('programs')->orderBy('name')->get();
    @endphp

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
                <li class="nav-item {{ $activeNav === 'analytics' ? 'active' : '' }}">
                    <a href="{{ route('admin.analytics') }}" class="nav-link" @if($activeNav === 'analytics') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="people" aria-expanded="true">People</button>
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
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="school" aria-expanded="true">School</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'departments' ? 'active' : '' }}">
                    <a href="{{ route('admin.departments.index') }}" class="nav-link" @if($activeNav === 'departments') aria-current="page" @endif>
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Departments</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'programs' ? 'active' : '' }}">
                    <a href="{{ route('admin.programs.index') }}" class="nav-link" @if($activeNav === 'programs') aria-current="page" @endif>
                        <i class="fa-solid fa-book-open"></i>
                        <span>Programs</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'sections' ? 'active' : '' }}">
                    <a href="{{ route('admin.sections.index') }}" class="nav-link" @if($activeNav === 'sections') aria-current="page" @endif>
                        <i class="fa-solid fa-users-rectangle"></i>
                        <span>Sections</span>
                    </a>
                </li>
            </ul>
        </div>


        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academic" aria-expanded="true">Academic</button>
            <ul class="nav-list">
                @foreach($departments as $department)
                    @php
                        $isDepartmentActive = in_array($activeNav, ['courses', 'modules', 'lessons', 'classes']) && request()->get('department') == $department->code;
                    @endphp
                    <li class="nav-item nav-item--nested">
                        <button type="button" class="nav-link nav-link--expandable" data-sidebar-subgroup="academic-{{ $department->code }}" aria-expanded="{{ $isDepartmentActive ? 'true' : 'false' }}">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>{{ $department->name }}</span>
                            <i class="fa-solid fa-chevron-down nav-link__chevron"></i>
                        </button>
                        <ul class="nav-list nav-list--nested {{ $isDepartmentActive ? '' : 'hidden' }}" id="academic-{{ $department->code }}">
                            @if($department->programs->count() > 0)
                                @foreach($department->programs->sortBy('name') as $program)
                                    @php
                                        $isProgramActive = in_array($activeNav, ['courses', 'modules', 'lessons', 'classes']) && request()->get('program') == $program->code;
                                    @endphp
                                    <li class="nav-item nav-item--program">
                                        <button type="button" class="nav-link nav-link--program-toggle" data-sidebar-program="program-{{ $program->id }}" aria-expanded="{{ $isProgramActive ? 'true' : 'false' }}">
                                            <i class="fa-solid fa-book-open"></i>
                                            <span>{{ $program->name }}</span>
                                            <i class="fa-solid fa-chevron-right nav-link__chevron"></i>
                                        </button>
                                        <ul class="nav-list nav-list--nested nav-list--program {{ $isProgramActive ? '' : 'hidden' }}" id="program-{{ $program->id }}">
                                            <li class="nav-item {{ $activeNav === 'courses' && request()->get('program') == $program->code ? 'active' : '' }}">
                                                <a href="{{ route('admin.courses.index', ['department' => $department->code, 'program' => $program->code]) }}" class="nav-link" @if($activeNav === 'courses' && request()->get('program') == $program->code) aria-current="page" @endif>
                                                    <i class="fa-solid fa-book"></i>
                                                    <span>Courses</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $activeNav === 'modules' && request()->get('program') == $program->code ? 'active' : '' }}">
                                                <a href="{{ route('admin.modules.index', ['department' => $department->code, 'program' => $program->code]) }}" class="nav-link" @if($activeNav === 'modules' && request()->get('program') == $program->code) aria-current="page" @endif>
                                                    <i class="fa-solid fa-layer-group"></i>
                                                    <span>Modules</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $activeNav === 'lessons' && request()->get('program') == $program->code ? 'active' : '' }}">
                                                <a href="{{ route('admin.lessons.index', ['department' => $department->code, 'program' => $program->code]) }}" class="nav-link" @if($activeNav === 'lessons' && request()->get('program') == $program->code) aria-current="page" @endif>
                                                    <i class="fa-solid fa-book-open-reader"></i>
                                                    <span>Lessons</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $activeNav === 'classes' && request()->get('program') == $program->code ? 'active' : '' }}">
                                                <a href="{{ route('admin.classes.index', ['department' => $department->code, 'program' => $program->code]) }}" class="nav-link" @if($activeNav === 'classes' && request()->get('program') == $program->code) aria-current="page" @endif>
                                                    <i class="fa-solid fa-school"></i>
                                                    <span>Classes</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endforeach
                            @else
                                <li class="nav-item {{ $activeNav === 'courses' && request()->get('department') == $department->code ? 'active' : '' }}">
                                    <a href="{{ route('admin.courses.index', ['department' => $department->code]) }}" class="nav-link" @if($activeNav === 'courses' && request()->get('department') == $department->code) aria-current="page" @endif>
                                        <i class="fa-solid fa-book"></i>
                                        <span>Courses</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ $activeNav === 'modules' && request()->get('department') == $department->code ? 'active' : '' }}">
                                    <a href="{{ route('admin.modules.index', ['department' => $department->code]) }}" class="nav-link" @if($activeNav === 'modules' && request()->get('department') == $department->code) aria-current="page" @endif>
                                        <i class="fa-solid fa-layer-group"></i>
                                        <span>Modules</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ $activeNav === 'lessons' && request()->get('department') == $department->code ? 'active' : '' }}">
                                    <a href="{{ route('admin.lessons.index', ['department' => $department->code]) }}" class="nav-link" @if($activeNav === 'lessons' && request()->get('department') == $department->code) aria-current="page" @endif>
                                        <i class="fa-solid fa-book-open-reader"></i>
                                        <span>Lessons</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ $activeNav === 'classes' && request()->get('department') == $department->code ? 'active' : '' }}">
                                    <a href="{{ route('admin.classes.index', ['department' => $department->code]) }}" class="nav-link" @if($activeNav === 'classes' && request()->get('department') == $department->code) aria-current="page" @endif>
                                        <i class="fa-solid fa-school"></i>
                                        <span>Classes</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endforeach
                <li class="nav-item {{ $activeNav === 'schedules' ? 'active' : '' }}">
                    <a href="{{ route('admin.classes.schedules.index') }}" class="nav-link" @if($activeNav === 'schedules') aria-current="page" @endif>
                        <i class="fa-solid fa-clock"></i>
                        <span>Schedules</span>
                    </a>
                </li>
                <li class="nav-item {{ in_array($activeNav, ['enrollment', 'enrollments'], true) ? 'active' : '' }}">
                    <a href="{{ route('admin.enrollments.index') }}" class="nav-link" @if(in_array($activeNav, ['enrollment', 'enrollments'], true)) aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'academic_periods' ? 'active' : '' }}">
                    <a href="{{ route('admin.academic_periods.index') }}" class="nav-link" @if($activeNav === 'academic_periods') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar"></i>
                        <span>Academic Periods</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'competencies' ? 'active' : '' }}">
                    <a href="{{ route('admin.competencies.index') }}" class="nav-link" @if($activeNav === 'competencies') aria-current="page" @endif>
                        <i class="fa-solid fa-star"></i>
                        <span>Competencies</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessment" aria-expanded="true">Assessment</button>
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
                <li class="nav-item {{ $activeNav === 'question_banks' ? 'active' : '' }}">
                    <a href="{{ route('admin.question_banks.index') }}" class="nav-link" @if($activeNav === 'question_banks') aria-current="page" @endif>
                        <i class="fa-solid fa-folder-closed"></i>
                        <span>Question Banks</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'rubrics' ? 'active' : '' }}">
                    <a href="{{ route('admin.rubrics.index') }}" class="nav-link" @if($activeNav === 'rubrics') aria-current="page" @endif>
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rubrics</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('admin.gradebook.index') }}" class="nav-link" @if($activeNav === 'gradebook') aria-current="page" @endif>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Gradebook</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'grade_status' ? 'active' : '' }}">
                    <a href="{{ route('admin.gradebook.grades.status') }}" class="nav-link" @if($activeNav === 'grade_status') aria-current="page" @endif>
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>Grade Status</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'attendance' ? 'active' : '' }}">
                    <a href="{{ route('admin.attendance.index') }}" class="nav-link" @if($activeNav === 'attendance') aria-current="page" @endif>
                        <i class="fa-solid fa-clipboard-user"></i>
                        <span>Attendance</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="engagement" aria-expanded="true">Engagement</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'announcements' ? 'active' : '' }}">
                    <a href="{{ route('admin.announcements.index') }}" class="nav-link" @if($activeNav === 'announcements') aria-current="page" @endif>
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'calendar' ? 'active' : '' }}">
                    <a href="{{ route('admin.calendar.index') }}" class="nav-link" @if($activeNav === 'calendar') aria-current="page" @endif>
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'virtual_classes' ? 'active' : '' }}">
                    <a href="{{ route('admin.virtual_classes.index') }}" class="nav-link" @if($activeNav === 'virtual_classes') aria-current="page" @endif>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'notifications' ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.index') }}" class="nav-link" @if($activeNav === 'notifications') aria-current="page" @endif>
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'reports' ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.index') }}" class="nav-link" @if($activeNav === 'reports') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'settings' ? 'active' : '' }}">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link nav-link--settings" title="System settings" @if($activeNav === 'settings') aria-current="page" @endif>
                        <i class="fa-solid fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'permissions' ? 'active' : '' }}">
                    <a href="{{ route('admin.permissions.index') }}" class="nav-link" title="Manage role permissions" @if($activeNav === 'permissions') aria-current="page" @endif>
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Permissions</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'backup' ? 'active' : '' }}">
                    <a href="{{ route('admin.backup.index') }}" class="nav-link" title="Backup and restore" @if($activeNav === 'backup') aria-current="page" @endif>
                        <i class="fa-solid fa-database"></i>
                        <span>Backup</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'audit_logs' ? 'active' : '' }}">
                    <a href="{{ route('admin.audit_logs.index') }}" class="nav-link" @if($activeNav === 'audit_logs') aria-current="page" @endif>
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
