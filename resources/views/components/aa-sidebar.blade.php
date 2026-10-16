<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">{{ auth()->user()->role?->name ?? 'AA Staff' }} Panel (View-Only)</span></div></div>
    </div>

    {{-- View-only panel: walang create/edit/delete routes ang AA Staff.
         Ang official data at final grades ay hawak sa AA system. --}}
    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Overview</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('aa.dashboard') }}" class="nav-link" @if($activeNav === 'dashboard') aria-current="page" @endif>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academics" aria-expanded="true">Academic Records (Read-Only)</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('aa.courses.index') }}" class="nav-link" @if($activeNav === 'courses') aria-current="page" @endif>
                        <i class="fa-solid fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'students' ? 'active' : '' }}">
                    <a href="{{ route('aa.students.index') }}" class="nav-link" @if($activeNav === 'students') aria-current="page" @endif>
                        <i class="fa-solid fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('aa.enrollments.index') }}" class="nav-link" @if($activeNav === 'enrollments') aria-current="page" @endif>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="progress" aria-expanded="true">LMS Progress (Read-Only)</button>
            <ul class="nav-list">
                <li class="nav-item {{ $activeNav === 'progress' ? 'active' : '' }}">
                    <a href="{{ route('aa.progress.index') }}" class="nav-link" @if($activeNav === 'progress') aria-current="page" @endif>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Student Progress</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'grade_submissions' ? 'active' : '' }}">
                    <a href="{{ route('aa.grade-submissions.index') }}" class="nav-link" @if($activeNav === 'grade_submissions') aria-current="page" @endif>
                        <i class="fa-solid fa-file-export"></i>
                        <span>Grade Submissions</span>
                    </a>
                </li>
                <li class="nav-item {{ $activeNav === 'reports' ? 'active' : '' }}">
                    <a href="{{ route('aa.reports.index') }}" class="nav-link" @if($activeNav === 'reports') aria-current="page" @endif>
                        <i class="fa-solid fa-file-lines"></i>
                        <span>LMS Reports</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
