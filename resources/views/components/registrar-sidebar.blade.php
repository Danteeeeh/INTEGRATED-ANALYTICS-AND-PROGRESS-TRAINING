<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo"><div><h2>{{ config('app.name') }}</h2>
        <span class="sidebar-subtitle">{{ auth()->user()->role?->name ?? 'Registrar' }} / Staff Panel</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item {{ ($activeNav ?? '') === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link" @if(($activeNav ?? '') === 'dashboard') aria-current="page" @endif><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academics" aria-expanded="true">Academic Management</button>
            <ul class="nav-list">
                <li class="nav-item {{ ($activeNav ?? '') === 'courses' ? 'active' : '' }}">
                    <a href="{{ route('admin.courses.index') }}" class="nav-link" @if(($activeNav ?? '') === 'courses') aria-current="page" @endif><i class="fa-solid fa-book"></i><span>Courses</span></a>
                </li>
                <li class="nav-item {{ ($activeNav ?? '') === 'classes' ? 'active' : '' }}">
                    <a href="{{ route('admin.classes.index') }}" class="nav-link" @if(($activeNav ?? '') === 'classes') aria-current="page" @endif><i class="fa-solid fa-school"></i><span>Classes</span></a>
                </li>
                <li class="nav-item {{ ($activeNav ?? '') === 'enrollments' ? 'active' : '' }}">
                    <a href="{{ route('admin.enrollments.index') }}" class="nav-link" @if(($activeNav ?? '') === 'enrollments') aria-current="page" @endif><i class="fa-solid fa-user-plus"></i><span>Enrollments</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="students" aria-expanded="true">Student Management</button>
            <ul class="nav-list">
                <li class="nav-item {{ ($activeNav ?? '') === 'students' ? 'active' : '' }}">
                    <a href="{{ route('admin.students.index') }}" class="nav-link" @if(($activeNav ?? '') === 'students') aria-current="page" @endif><i class="fa-solid fa-user-graduate"></i><span>Students</span></a>
                </li>
                <li class="nav-item {{ ($activeNav ?? '') === 'gradebook' ? 'active' : '' }}">
                    <a href="{{ route('admin.gradebook.index') }}" class="nav-link" @if(($activeNav ?? '') === 'gradebook') aria-current="page" @endif><i class="fa-solid fa-graduation-cap"></i><span>Gradebook</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="reporting" aria-expanded="true">Reporting</button>
            <ul class="nav-list">
                <li class="nav-item {{ ($activeNav ?? '') === 'reports' ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.index') }}" class="nav-link" @if(($activeNav ?? '') === 'reports') aria-current="page" @endif><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
                </li>
                <li class="nav-item {{ ($activeNav ?? '') === 'analytics' ? 'active' : '' }}">
                    <a href="{{ route('admin.analytics') }}" class="nav-link" @if(($activeNav ?? '') === 'analytics') aria-current="page" @endif><i class="fa-solid fa-chart-pie"></i><span>Analytics</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
                <li class="nav-item {{ ($activeNav ?? '') === 'calendar' ? 'active' : '' }}">
                    <a href="{{ route('admin.calendar.index') }}" class="nav-link" @if(($activeNav ?? '') === 'calendar') aria-current="page" @endif><i class="fa-solid fa-calendar"></i><span>Calendar</span></a>
                </li>
            </ul>
        </div>
    </nav>
</aside>