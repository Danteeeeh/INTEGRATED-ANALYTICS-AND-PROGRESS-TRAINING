<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">{{ auth()->user()->role?->name ?? 'Super Admin' }} Panel</span></div></div>
    </div>

    {{-- ===================================================================
         SUPER ADMIN PANEL (Canvas-style, strict scope)

         Ang Super Admin (IT) ay: (1) tagapag-sync mula AA → LMS,
         (2) manager ng courses. Iyon lang. Ang enrollment at official
         records ay galing na sa AA (Sync from AA), ang academic operations
         ay sa Faculty, at view-only ay sa AA Staff.

         Ang mga nakatagong nav group ay naka-comment sa ibaba — routes at
         tests ay buhay pa (route-level access intact), itago lang ang UI
         para hindi magulo ang panel.
         =================================================================== --}}
    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                {{-- Primary Super Admin action: enrollment/official records come from AA. --}}
                <li class="nav-item {{ $activeNav === 'aa-sync' ? 'active' : '' }}">
                    <a href="{{ route('admin.aa-sync.index') }}" class="nav-link" @if($activeNav === 'aa-sync') aria-current="page" @endif>
                        <i class="fa-solid fa-rotate"></i>
                        <span>Sync from AA</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link" @if($activeNav === 'dashboard') aria-current="page" @endif>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academics" aria-expanded="true">Course Management</button>
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
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="users" aria-expanded="true">User Management</button>
            <ul class="nav-list">
                {{-- Accounts only (e.g. creating AA Staff accounts). Students,
                     instructors, enrollments, departments, programs, sections,
                     at classes ay galing na sa AA sync. --}}
                <li class="nav-item {{ $activeNav === 'users' ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="nav-link" @if($activeNav === 'users') aria-current="page" @endif>
                        <i class="fa-solid fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
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

    {{-- ═══════════════════════════════════════════════════════════════
         HIDDEN NAV GROUPS (Canvas-style scope — kept commented for reference)

         Classes, Assessments (assignments/quizzes/exams/question banks/
         test bank/rubrics), Classroom (virtual classes/announcements/
         attendance), Evaluation (gradebook/subjects/academic records/
         analytics/reports), Students, Instructors, Enrollments, Organization
         (departments/programs/sections/academic periods), System extras
         (calendar/competencies), Search, Terms of Service, Permissions.

         Rationale: assessments at progress ay Faculty; gradebook/records/
         reports ay AA; enrollment at org data ay AA sync. Ang routes ay
         buhay pa para sa tests at direct access kapag kailangan.
         ═══════════════════════════════════════════════════════════════

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academics" aria-expanded="true">Academic Management</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('admin.classes.index') }}" class="nav-link">
                        <i class="fa-solid fa-school"></i>
                        <span>Classes</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessments" aria-expanded="true">Assessments</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.assignments.index') }}" class="nav-link"><i class="fa-solid fa-tasks"></i><span>Assignments</span></a></li>
                <li><a href="{{ route('admin.quizzes.index') }}" class="nav-link"><i class="fa-solid fa-question-circle"></i><span>Quizzes</span></a></li>
                <li><a href="{{ route('admin.exams.index') }}" class="nav-link"><i class="fa-solid fa-file-signature"></i><span>Exams</span></a></li>
                <li><a href="{{ route('admin.question_banks.index') }}" class="nav-link"><i class="fa-solid fa-database"></i><span>Question Banks</span></a></li>
                <li><a href="{{ route('admin.test_bank.index') }}" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Test Bank</span></a></li>
                <li><a href="{{ route('admin.rubrics.index') }}" class="nav-link"><i class="fa-solid fa-list-check"></i><span>Rubrics</span></a></li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="classroom" aria-expanded="true">Classroom</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.virtual_classes.index') }}" class="nav-link"><i class="fa-solid fa-video"></i><span>Virtual Classes</span></a></li>
                <li><a href="{{ route('admin.announcements.index') }}" class="nav-link"><i class="fa-solid fa-comments"></i><span>Announcements</span></a></li>
                <li><a href="{{ route('admin.attendance.index') }}" class="nav-link"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a></li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="evaluation" aria-expanded="true">Evaluation</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.gradebook.index') }}" class="nav-link"><i class="fa-solid fa-graduation-cap"></i><span>Gradebook</span></a></li>
                <li><a href="{{ route('admin.subjects.index') }}" class="nav-link"><i class="fa-solid fa-book-open"></i><span>LMS Subjects</span></a></li>
                <li><a href="{{ route('admin.academic-records.index') }}" class="nav-link"><i class="fa-solid fa-file-lines"></i><span>Academic Records</span></a></li>
                <li><a href="{{ route('admin.analytics') }}" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Analytics</span></a></li>
                <li><a href="{{ route('admin.reports.index') }}" class="nav-link"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a></li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="users" aria-expanded="true">User Management</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.students.index') }}" class="nav-link"><i class="fa-solid fa-user-graduate"></i><span>Students</span></a></li>
                <li><a href="{{ route('admin.instructors.index') }}" class="nav-link"><i class="fa-solid fa-chalkboard-user"></i><span>Instructors</span></a></li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="organization" aria-expanded="true">Organization</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.departments.index') }}" class="nav-link"><i class="fa-solid fa-building"></i><span>Departments</span></a></li>
                <li><a href="{{ route('admin.programs.index') }}" class="nav-link"><i class="fa-solid fa-sitemap"></i><span>Programs</span></a></li>
                <li><a href="{{ route('admin.sections.index') }}" class="nav-link"><i class="fa-solid fa-layer-group"></i><span>Sections</span></a></li>
                <li><a href="{{ route('admin.academic_periods.index') }}" class="nav-link"><i class="fa-solid fa-calendar-days"></i><span>Academic Periods</span></a></li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System (extras)</button>
            <ul class="nav-list">
                <li><a href="{{ route('admin.calendar.index') }}" class="nav-link"><i class="fa-solid fa-calendar"></i><span>Calendar</span></a></li>
                <li><a href="{{ route('admin.competencies.index') }}" class="nav-link"><i class="fa-solid fa-medal"></i><span>Competencies</span></a></li>
            </ul>
        </div>
        ═══════════════════════════════════════════════════════════════ --}}
</aside>
