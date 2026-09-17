@extends('layouts.registrar')

@section('title', 'Registrar Dashboard')
@php
    $activeNav = 'dashboard';
    $pageTitle = 'Registrar Dashboard';
    $pageIcon = '<i class="fa-solid fa-folder-open"></i>';
    $stats ??= [];
    $pActive = $stats['active_enrollments'] ?? 0;
    $pPending = $stats['pending_enrollments'] ?? 0;
    $pCompleted = $stats['completed_enrollments'] ?? 0;
    $pDropped = $stats['dropped_enrollments'] ?? 0;
    $pTotal = max($pActive + $pPending + $pCompleted, 1);
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-folder-open"></i>
            Registrar Dashboard
        </h2>
    </div>
@endsection

@section('content')
<div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="Welcome, {{ auth()->user()->name ?? 'Registrar' }}"
        subtitle="Manage student records and enrollments without changing course content or system settings."
        icon="fa-folder-open"
        kicker="Registrar / Staff"
    >
        <x-slot name="meta">
            <span class="live-dot"></span>
            <span>{{ now()->format('l, F j, Y') }}</span>
            @if($stats['current_period'] ?? null)
                <span>·</span>
                <span>{{ $stats['current_period']->name }}</span>
            @endif
            <span>·</span>
            <span>{{ $stats['published_courses'] ?? 0 }} courses published</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('registrar.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="{{ route('registrar.enrollments.create') }}" class="btn btn-secondary"><i class="fa-solid fa-user-plus"></i> Enroll student</a>
            <a href="{{ route('registrar.reports.index') }}" class="btn btn-secondary"><i class="fa-solid fa-file-lines"></i> Reports</a>
            <button onclick="refreshDashboard()" class="btn btn-secondary"><i class="fa-solid fa-sync-alt"></i> Refresh</button>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ SEARCH BAR ═══ --}}
    <section class="dash-search">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" id="globalSearch" placeholder="Search students, enrollments, classes, courses..." class="search-input">
                <select id="searchType" class="search-type">
                    <option value="all">All</option>
                    <option value="students">Students</option>
                    <option value="enrollments">Enrollments</option>
                    <option value="classes">Classes</option>
                    <option value="courses">Courses</option>
                </select>
            </div>
            <div id="searchResults" class="search-results hidden"></div>
        </div>
    </section>

    {{-- ═══ STAT TILES ═══ --}}
    <div class="user-stat-grid">
        <x-user-stat-card
            label="Total Students"
            value="{{ $stats['total_students'] ?? 0 }}"
            icon="fa-user-graduate"
            valueId="stat-students-value"
            trend="{{ $stats['active_students'] ?? 0 }} active"
            footer="{{ $stats['pending_students'] ?? 0 }} pending accounts"
        />
        <x-user-stat-card
            label="Active Enrollments"
            value="{{ $pActive }}"
            icon="fa-user-plus"
            valueId="stat-active-value"
            trend="Live"
        >
            <x-slot name="footer"><i class="fa-solid fa-circle-check" style="color:#34d399"></i> <span id="stat-completed-value">{{ $pCompleted }}</span> completed</x-slot>
        </x-user-stat-card>
        <x-user-stat-card
            label="Pending Enrollments"
            value="{{ $pPending }}"
            icon="fa-clock"
            valueId="stat-pending-value"
            trend="{{ $pPending > 0 ? 'Action needed' : 'All processed' }}"
            footer="Awaiting confirmation"
        />
        <x-user-stat-card
            label="Classes"
            value="{{ $stats['total_classes'] ?? 0 }}"
            icon="fa-school"
            trend="{{ $stats['active_classes'] ?? 0 }} running"
            footer="{{ $stats['total_courses'] ?? 0 }} courses"
        />
    </div>

    {{-- ═══ ENROLLMENT PIPELINE ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-diagram-project"></i> Enrollment pipeline</h3>
            <span class="dash-section-kicker">Distribution of enrollment statuses</span>
        </div>
    </div>
    <section class="dash-panel" style="margin-bottom:22px">
        <div class="pipeline">
            <span class="pl-active" style="width: {{ ($pActive / $pTotal) * 100 }}%"></span>
            <span class="pl-pending" style="width: {{ ($pPending / $pTotal) * 100 }}%"></span>
            <span class="pl-completed" style="width: {{ ($pCompleted / $pTotal) * 100 }}%"></span>
        </div>
        <div class="pipeline-legend">
            <span><i class="pl-dot-active"></i> {{ $pActive }} Active</span>
            <span><i class="pl-dot-pending"></i> {{ $pPending }} Pending</span>
            <span><i class="pl-dot-completed"></i> {{ $pCompleted }} Completed</span>
            @if($pDropped > 0)
                <span><i class="pl-dot-gray"></i> {{ $pDropped }} Dropped</span>
            @endif
        </div>
    </section>

    {{-- ═══ RECENT ENROLLMENTS + RECENT ACTIVITY ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-user-plus"></i> Recent enrollments</h4>
                <a href="{{ route('registrar.enrollments.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
            </div>
            <ul class="dash-list">
                @forelse(($stats['recent_enrollments'] ?? []) as $enrollment)
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-green"><i class="fa-solid fa-user-graduate"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $enrollment->student?->name }}</p>
                            <p class="dash-list-sub">{{ $enrollment->class?->course?->title ?? $enrollment->class?->code }}</p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip {{ $enrollment->status === 'active' ? 'm-green' : ($enrollment->status === 'pending' ? 'm-amber' : 'm-gray') }}">{{ ucfirst($enrollment->status) }}</span>
                            <span class="dash-list-date">{{ $enrollment->created_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-user-plus"></i> No recent enrollments</li>
                @endforelse
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-clock-rotate-left"></i> Recent activity</h4>
                <span class="panel-count">{{ ($stats['recent_activity'] ?? collect())->count() }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($stats['recent_activity'] ?? []) as $log)
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-violet"><i class="fa-solid fa-file-shield"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $log->action ?? 'Activity' }}</p>
                            <p class="dash-list-sub">{{ $log->user?->name ?? 'System' }} · {{ Str::limit($log->resource_type ?? '', 24) }}</p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-list-date">{{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-clock"></i> No recent activity</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ═══ RECENT STUDENTS + RECENT COURSES ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-users"></i> Recently added students</h4>
                <a href="{{ route('registrar.students.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
            </div>
            <ul class="dash-list">
                @forelse(($stats['recent_students'] ?? []) as $student)
                    <li class="dash-list-item">
                        <span class="dash-list-icon c-cyan" style="display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:rgba(34,211,238,.13);color:#67e8f9"><i class="fa-solid fa-user"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $student->name }}</p>
                            <p class="dash-list-sub">{{ $student->email }}</p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip {{ $student->status === 'active' ? 'm-green' : 'm-amber' }}">{{ ucfirst($student->status ?? 'active') }}</span>
                            <span class="dash-list-date">{{ $student->created_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-users"></i> No students yet</li>
                @endforelse
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-book-open"></i> Recent courses</h4>
                <a href="{{ route('registrar.courses.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
            </div>
            <ul class="dash-list">
                @forelse(($stats['recent_courses'] ?? []) as $course)
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-violet"><i class="fa-solid fa-book"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $course->title }}</p>
                            <p class="dash-list-sub">{{ $course->code ?? '' }} · {{ $course->courseCategory?->name ?? '' }}</p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip {{ $course->status === 'published' ? 'm-green' : ($course->status === 'draft' ? 'm-amber' : 'm-gray') }}">{{ ucfirst($course->status ?? 'draft') }}</span>
                            <span class="dash-list-date">{{ $course->created_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-book"></i> No courses yet</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ═══ ANALYTICS OVERVIEW ═══ --}}
    <section class="dash-analytics">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-chart-pie"></i> Registrar Analytics</h4>
            <div class="analytics-controls">
                <select id="analyticsType" class="analytics-select">
                    <option value="enrollment" selected>Enrollments</option>
                    <option value="classes">Classes</option>
                    <option value="students">Students</option>
                </select>
                <button onclick="loadAnalytics()" class="analytics-btn"><i class="fa-solid fa-sync-alt"></i> Load</button>
            </div>
        </div>
        <div class="analytics-content">
            <div id="analytics-toast" class="analytics-toast"></div>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-diagram-project"></i> Enrollment Status</h5>
                    <div id="enrollmentStatusChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-pie"></i><p>Loading analytics…</p></div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-school"></i> Classes by Course</h5>
                    <div id="classesByCourseChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-bar"></i><p>Loading analytics…</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ FUNCTIONALITY ═══ --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Real-time dashboard refresh
        async function refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            try {
                await fetch('{{ route('registrar.dashboard.clear-cache') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                refreshBtn.innerHTML = originalContent;
                refreshBtn.disabled = false;
                if (typeof LMS !== 'undefined' && LMS.toast) {
                    LMS.toast('Error refreshing dashboard. Please try again.', 'error');
                } else {
                    alert('Error refreshing dashboard. Please try again.');
                }
            }
        }

        // Auto-refresh stats every 30s
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('registrar.dashboard.real-time-stats') }}');
                if (!response.ok) throw new Error('Bad status: ' + response.status);
                const data = await response.json();
                if (data.success) {
                    const s = data.data;
                    const stu = document.getElementById('stat-students-value');
                    if (stu && s.total_students !== undefined) stu.textContent = s.total_students;
                    const act = document.getElementById('stat-active-value');
                    if (act && s.active_enrollments !== undefined) act.textContent = s.active_enrollments;
                    const pen = document.getElementById('stat-pending-value');
                    if (pen && s.pending_enrollments !== undefined) pen.textContent = s.pending_enrollments;
                }
            } catch (error) {
                console.error('Error fetching real-time stats:', error);
            }
        }, 30000);

        // Search functionality
        const searchInput = document.getElementById('globalSearch');
        const searchType = document.getElementById('searchType');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }
            searchResults.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.classList.remove('hidden');
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });

        async function performSearch(query) {
            const type = searchType.value;
            try {
                const response = await fetch(`{{ route('registrar.dashboard.search') }}?query=${encodeURIComponent(query)}&type=${type}`);
                const data = await response.json();
                if (data.success) {
                    if (data.data.length > 0) {
                        displaySearchResults(data.data, type);
                    } else {
                        searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                        searchResults.classList.remove('hidden');
                    }
                } else {
                    throw new Error(data.error || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-no-results">Error: ' + error.message + '</div>';
                searchResults.classList.remove('hidden');
            }
        }

        function typeBadge(result, type) {
            if (type !== 'all') return '';
            let inferred = 'courses';
            if (result.email) inferred = 'students';
            else if (result.class_code && result.course_title && result.name) inferred = 'enrollments';
            else if (result.course_title && result.code && !result.name) inferred = 'classes';
            const map = {
                'students': ['Student', 'fa-user-graduate', 'type-student'],
                'enrollments': ['Enrollment', 'fa-user-plus', 'type-assignment'],
                'classes': ['Class', 'fa-school', 'type-class'],
                'courses': ['Course', 'fa-book', 'type-course'],
            };
            const [label, icon, cls] = map[inferred];
            return `<span class="type-badge ${cls}"><i class="fa-solid ${icon}"></i> ${label}</span>`;
        }

        function displaySearchResults(results, type) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                searchResults.classList.remove('hidden');
                return;
            }
            let html = '<div class="search-results-list">';
            results.forEach(result => {
                let href = '#';
                if (type === 'students' || result.email) {
                    href = result.id ? `{{ url('registrar/students') }}/${result.id}` : '#';
                } else if (type === 'enrollments' || (result.name && result.class_code)) {
                    href = result.id ? `{{ url('registrar/enrollments') }}` : '#';
                } else if (type === 'classes' || (result.code && result.course_title && !result.title)) {
                    href = result.id ? `{{ url('registrar/classes') }}/${result.id}` : '#';
                } else if (type === 'courses' || result.title) {
                    href = result.id ? `{{ url('registrar/courses') }}/${result.id}` : '#';
                }
                html += `
                    <a href="${href}" class="search-result-item">
                        <div class="search-result-title">${result.name || result.title || result.code} ${typeBadge(result, type)}</div>
                        <div class="search-result-details">
                            ${result.email ? `<span>${result.email}</span>` : ''}
                            ${result.identifier ? `<span>ID: ${result.identifier}</span>` : ''}
                            ${result.course_title ? `<span>${result.course_title}</span>` : ''}
                            ${result.class_code ? `<span>${result.class_code}</span>` : ''}
                            ${result.status ? `<span class="status-badge ${result.status}">${result.status}</span>` : ''}
                        </div>
                    </a>
                `;
            });
            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');
        }

        // Close search when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) searchResults.classList.add('hidden');
        });

        // Analytics
        let chartInstances = {};
        function destroyChart(id) {
            if (chartInstances[id]) { chartInstances[id].destroy(); delete chartInstances[id]; }
        }

        async function loadAnalytics() {
            const type = document.getElementById('analyticsType').value;
            const button = document.querySelector('.analytics-btn');
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            button.disabled = true;
            try {
                const response = await fetch(`{{ route('registrar.dashboard.analytics') }}?type=${type}`);
                const data = await response.json();
                if (data.success) {
                    displayAnalytics(data.data, type);
                    const toast = document.getElementById('analytics-toast');
                    if (toast) {
                        toast.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} analytics loaded`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                } else {
                    throw new Error(data.error || 'Failed to load analytics');
                }
            } catch (error) {
                console.error('Analytics error:', error);
                if (typeof LMS !== 'undefined' && LMS.toast) {
                    LMS.toast('Error loading analytics: ' + error.message, 'error');
                } else {
                    alert('Error loading analytics: ' + error.message);
                }
            } finally {
                button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load';
                button.disabled = false;
            }
        }

        function displayAnalytics(analytics, type) {
            if (type === 'enrollment') displayEnrollmentStatus(analytics);
            else if (type === 'classes') displayClassesByCourse(analytics);
            else if (type === 'students') displayStudents(analytics);
        }

        function displayEnrollmentStatus(a) {
            const container = document.getElementById('enrollmentStatusChart');
            destroyChart('enrollmentStatusChart');
            container.innerHTML = '<canvas id="enrollCanvas"></canvas>';
            chartInstances['enrollmentStatusChart'] = new Chart(document.getElementById('enrollCanvas'), {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Pending', 'Completed', 'Dropped'],
                    datasets: [{
                        data: [a.active, a.pending, a.completed, a.dropped],
                        backgroundColor: ['#34d399', '#fbbf24', '#62c9f5', '#fb7185'],
                        borderColor: '#151c2c',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#c7d4ec', boxWidth: 12, padding: 12 } },
                        tooltip: { callbacks: { label: (c) => ` ${c.parsed} enrollments` } }
                    }
                }
            });
        }

        function displayClassesByCourse(a) {
            const container = document.getElementById('classesByCourseChart');
            destroyChart('classesByCourseChart');
            container.innerHTML = '<canvas id="classesCanvas"></canvas>';
            const byCourse = a.by_course || [];
            chartInstances['classesByCourseChart'] = new Chart(document.getElementById('classesCanvas'), {
                type: 'bar',
                data: {
                    labels: byCourse.length ? byCourse.map(c => c.course.length > 18 ? c.course.slice(0, 18) + '…' : c.course) : ['No data'],
                    datasets: [{
                        label: 'Classes',
                        data: byCourse.length ? byCourse.map(c => c.total) : [0],
                        backgroundColor: 'rgba(98, 201, 245, 0.6)',
                        borderColor: '#62c9f5',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 44
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#98a7c4' }, grid: { color: 'rgba(153,174,214,.12)' } },
                        x: { grid: { display: false }, ticks: { color: '#c7d4ec', maxRotation: 30 } }
                    }
                }
            });
        }

        function displayStudents(a) {
            const container = document.getElementById('enrollmentStatusChart');
            destroyChart('enrollmentStatusChart');
            container.innerHTML = '<canvas id="studentsCanvas"></canvas>';
            chartInstances['enrollmentStatusChart'] = new Chart(document.getElementById('studentsCanvas'), {
                type: 'bar',
                data: {
                    labels: ['Total', 'Active', 'Pending'],
                    datasets: [{
                        label: 'Students',
                        data: [a.total, a.active, a.pending],
                        backgroundColor: ['rgba(59,130,246,.6)', 'rgba(52,211,153,.6)', 'rgba(251,191,36,.6)'],
                        borderColor: ['#3b82f6', '#34d399', '#fbbf24'],
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 60
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#98a7c4' }, grid: { color: 'rgba(153,174,214,.12)' } },
                        x: { grid: { display: false }, ticks: { color: '#c7d4ec' } }
                    }
                }
            });
        }

        // Auto-load enrollment analytics
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.analytics-btn');
            if (button) { button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...'; button.disabled = true; }
            fetch(`{{ route('registrar.dashboard.analytics') }}?type=enrollment`)
                .then(r => r.json())
                .then(data => { if (data.success) displayAnalytics(data.data, 'enrollment'); })
                .catch(err => console.error('Initial analytics error:', err))
                .finally(() => {
                    if (button) { button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load'; button.disabled = false; }
                });
        });
    </script>

    <style>
        .search-container { position: relative; max-width: 800px; margin: 0 auto; }
        .dash-search { margin: 20px 0; }
        .search-input-wrapper {
            position: relative; display: flex; align-items: center;
            background: var(--dash-surface, #151c2c);
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; padding: 12px 16px;
            box-shadow: 0 2px 8px rgba(3,8,20,.25);
            transition: border-color .2s, box-shadow .2s;
        }
        .search-input-wrapper:focus-within { border-color: rgba(98,201,245,.45); box-shadow: 0 0 0 3px rgba(77,143,240,.15); }
        .search-icon { color: var(--dash-muted, #98a7c4); margin-right: 12px; }
        .search-input { flex: 1; border: none; outline: none; font-size: 16px; padding: 8px 0; background: transparent; color: var(--dash-text, #eef4ff); }
        .search-input::placeholder { color: #7f91b0; }
        .search-type {
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            background: var(--dash-bg, #101625); color: var(--dash-text, #eef4ff);
            padding: 8px 12px; border-radius: 8px; margin-left: 12px; cursor: pointer; font-size: 14px;
        }
        .search-results {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--dash-surface-raised, #1b2437);
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; margin-top: 8px;
            box-shadow: 0 12px 32px rgba(3,8,20,.45);
            max-height: 400px; overflow-y: auto; z-index: 100;
        }
        .search-results.hidden { display: none; }
        .search-results-list { padding: 8px 0; }
        .search-result-item {
            display: block; padding: 12px 16px; border-bottom: 1px solid rgba(153,174,214,.08);
            cursor: pointer; transition: background-color 0.2s; text-decoration: none; color: inherit;
        }
        .search-result-item:hover { background-color: rgba(77,143,240,.1); }
        .search-result-title { font-weight: 600; color: var(--dash-text, #eef4ff); margin-bottom: 4px; }
        .search-result-details { font-size: 14px; color: var(--dash-muted, #98a7c4); display: flex; gap: 12px; flex-wrap: wrap; }
        .search-no-results, .search-loading { padding: 20px; text-align: center; color: var(--dash-muted, #98a7c4); }
        .type-badge {
            display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 999px;
            font-size: 11px; font-weight: 750; letter-spacing: .03em; margin-left: 8px; vertical-align: middle;
        }
        .type-badge.type-student { background: rgba(16,185,129,.13); color: #6ee7b7; }
        .type-badge.type-course { background: rgba(59,130,246,.14); color: #93c5fd; }
        .type-badge.type-class { background: rgba(34,211,238,.13); color: #67e8f9; }
        .type-badge.type-assignment { background: rgba(251,191,36,.13); color: #fcd34d; }
        .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-badge.active { background: rgba(16,185,129,.13); color: #6ee7b7; }
        .status-badge.pending { background: rgba(251,191,36,.13); color: #fcd34d; }
        .status-badge.published, .status-badge.completed { background: rgba(16,185,129,.13); color: #6ee7b7; }
        .status-badge.draft { background: rgba(251,191,36,.13); color: #fcd34d; }

        .dash-analytics { margin: 30px 0; padding: 20px; background: var(--dash-surface, #151c2c); border: 1px solid var(--dash-line, rgba(153,174,214,.18)); border-radius: 16px; box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3)); }
        .dash-analytics .dash-panel-head { padding: 0 0 16px; border-bottom: 1px solid var(--dash-line, rgba(153,174,214,.18)); margin-bottom: 20px; }
        .analytics-toast {
            display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 10px;
            background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; font-size: .8rem; font-weight: 650;
        }
        .analytics-toast.show { display: block; animation: fadeInOut 3s ease; }
        @keyframes fadeInOut { 0% { opacity: 0; transform: translateY(-4px); } 10%, 85% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; } }
        .analytics-controls { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .analytics-select {
            padding: 8px 12px; border: 1px solid var(--dash-line, rgba(153,174,214,.18)); border-radius: 8px;
            background: var(--dash-bg, #101625); color: var(--dash-text, #eef4ff); cursor: pointer; font-size: 14px;
        }
        .analytics-btn {
            padding: 8px 16px; background: linear-gradient(135deg, #2449c6, #173aa8); color: white;
            border: 1px solid #3159d1; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 650;
            transition: background-color 0.2s, transform .16s;
        }
        .analytics-btn:hover:not(:disabled) { background: linear-gradient(135deg, #4d8ff0, #2449c6); transform: translateY(-1px); }
        .analytics-btn:disabled { opacity: .7; cursor: default; }
        .analytics-content { margin-top: 20px; }
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .analytics-card {
            background: rgba(77,143,240,.04); border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; padding: 20px;
        }
        .analytics-card h5 { margin: 0 0 16px 0; font-size: 15px; font-weight: 700; color: var(--dash-text, #eef4ff); display: flex; align-items: center; gap: 8px; }
        .analytics-card h5 i { color: var(--bcp-cyan-400, #62c9f5); }
        .chart-container { position: relative; height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .chart-container canvas { max-height: 100%; }
        .chart-placeholder { text-align: center; color: var(--dash-muted, #98a7c4); }
        .chart-placeholder i { font-size: 48px; margin-bottom: 12px; color: rgba(153,174,214,.35); }
        .chart-placeholder p { margin: 0; font-size: 14px; }

        .pl-dot-gray { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; margin-right: 5px; }
        @media (max-width: 640px) {
            .analytics-grid { grid-template-columns: 1fr; }
            .dash-analytics { padding: 16px; }
        }
    </style>
</div>
@endsection
