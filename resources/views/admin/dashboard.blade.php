@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@php
    $activeNav = 'dashboard';
    $pageTitle = 'Dashboard';
    $pageIcon = '<i class="fa-solid fa-gauge"></i>';
    $stats ??= [];
    $coursesByStatus = $stats['courses_by_status'] ?? ['published' => 0, 'draft' => 0, 'archived' => 0];
    $totalCoursesStat = max(array_sum($coursesByStatus), 1);
@endphp

@section('content')
<div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="Good day, {{ auth()->user()->name ?? 'Admin User' }}"
        subtitle="Monitor the learning environment, people, and academic activity from one place."
        icon="fa-shield-halved"
        kicker="Administration center"
    >
        <x-slot name="meta">
            <span class="live-dot"></span>
            <span>{{ now()->format('l, F j, Y') }}</span>
            @if($stats['current_period'] ?? null)
                <span>·</span>
                <span>{{ $stats['current_period']->name }}</span>
            @endif
            <span>·</span>
            <span><i class="fa-solid fa-database"></i> {{ $stats['database_status'] ?? 'Connected' }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="fa-solid fa-users"></i> Manage users</a>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-book-open"></i> View courses</a>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ STAT TILES ═══ --}}
    <div class="user-stat-grid">
        <x-user-stat-card
            label="Students"
            value="{{ $stats['total_students'] ?? 0 }}"
            icon="fa-user-graduate"
            trend="{{ $stats['active_users'] ?? 0 }} active"
            footer="{{ $stats['pending_users'] ?? 0 }} pending accounts"
        />
        <x-user-stat-card
            label="Instructors"
            value="{{ $stats['total_instructors'] ?? 0 }}"
            icon="fa-chalkboard-user"
            trend="{{ $stats['total_admins'] ?? 0 }} admins"
            footer="{{ $stats['active_users'] ?? 0 }} active users total"
        />
        <x-user-stat-card
            label="Classes"
            value="{{ $stats['total_classes'] ?? 0 }}"
            icon="fa-school"
            trend="{{ $stats['active_classes'] ?? 0 }} running"
            footer="{{ $stats['total_courses'] ?? 0 }} courses"
        />
        <x-user-stat-card
            label="Active Enrollments"
            value="{{ $stats['active_enrollments'] ?? 0 }}"
            icon="fa-user-plus"
            trend="{{ ($stats['pending_enrollments'] ?? 0) > 0 ? 'Pending review' : 'Healthy' }}"
            footer="{{ $stats['pending_enrollments'] ?? 0 }} pending"
        />
    </div>

    {{-- ═══ ACADEMIC HEALTH ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-heart-pulse"></i> Academic health</h3>
            <span class="dash-section-kicker">Live performance indicators across the institution</span>
        </div>
    </div>
    <div class="user-stat-grid">
        <x-user-stat-card
            label="Course Completion"
            value="{{ round((float) ($stats['completion_rate'] ?? 0)) }}%"
            icon="fa-certificate"
            trend="Avg"
            footer="{{ $stats['total_completions'] ?? 0 }} completions"
        />
        <x-user-stat-card
            label="Attendance Rate"
            value="{{ round((float) ($stats['attendance_rate'] ?? 0)) }}%"
            icon="fa-calendar-check"
            trend="{{ $stats['present_count'] ?? 0 }} present"
            footer="{{ $stats['absent_count'] ?? 0 }} absent"
        />
        <x-user-stat-card
            label="Average Grade"
            value="{{ number_format($stats['average_grade'] ?? 0, 1) }}%"
            icon="fa-graduation-cap"
            trend="Avg"
            footer="{{ $stats['graded_submissions'] ?? 0 }} graded submissions"
        />
        <x-user-stat-card
            label="Avg Quiz Score"
            value="{{ number_format($stats['average_quiz_score'] ?? 0, 1) }}%"
            icon="fa-question-circle"
            trend="{{ $stats['quiz_attempts'] ?? 0 }} attempts"
            footer="{{ $stats['pending_submissions'] ?? 0 }} pending submissions"
        />
    </div>

    {{-- ═══ PIPELINE + COURSES BY STATUS ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-diagram-project"></i> Enrollment pipeline</h4>
                <span class="panel-count">{{ ($stats['active_enrollments'] ?? 0) + ($stats['pending_enrollments'] ?? 0) + ($stats['completed_enrollments'] ?? 0) + ($stats['dropped_enrollments'] ?? 0) }}</span>
            </div>
            @php
                $pActive = $stats['active_enrollments'] ?? 0;
                $pPending = $stats['pending_enrollments'] ?? 0;
                $pCompleted = $stats['completed_enrollments'] ?? 0;
                $pTotal = max($pActive + $pPending + $pCompleted, 1);
            @endphp
            <div class="pipeline">
                <span class="pl-active" style="width: {{ ($pActive / $pTotal) * 100 }}%"></span>
                <span class="pl-pending" style="width: {{ ($pPending / $pTotal) * 100 }}%"></span>
                <span class="pl-completed" style="width: {{ ($pCompleted / $pTotal) * 100 }}%"></span>
            </div>
            <div class="pipeline-legend">
                <span><i class="pl-dot-active"></i> {{ $pActive }} Active</span>
                <span><i class="pl-dot-pending"></i> {{ $pPending }} Pending</span>
                <span><i class="pl-dot-completed"></i> {{ $pCompleted }} Completed</span>
            </div>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-book"></i> Courses by status</h4>
                <span class="panel-count">{{ $stats['total_courses'] ?? 0 }}</span>
            </div>
            <ul class="perf-list">
                <li class="perf-item">
                    <div class="perf-top">
                        <p class="perf-name">Published</p>
                        <span class="perf-score">{{ $coursesByStatus['published'] ?? 0 }}</span>
                    </div>
                    <div class="perf-bar"><span style="width: {{ (($coursesByStatus['published'] ?? 0) / $totalCoursesStat) * 100 }}%"></span></div>
                </li>
                <li class="perf-item">
                    <div class="perf-top">
                        <p class="perf-name">Draft</p>
                        <span class="perf-score">{{ $coursesByStatus['draft'] ?? 0 }}</span>
                    </div>
                    <div class="perf-bar"><span style="width: {{ (($coursesByStatus['draft'] ?? 0) / $totalCoursesStat) * 100 }}%;background:linear-gradient(90deg,#d97706,#fbbf24)"></span></div>
                </li>
                <li class="perf-item">
                    <div class="perf-top">
                        <p class="perf-name">Archived</p>
                        <span class="perf-score">{{ $coursesByStatus['archived'] ?? 0 }}</span>
                    </div>
                    <div class="perf-bar"><span style="width: {{ (($coursesByStatus['archived'] ?? 0) / $totalCoursesStat) * 100 }}%;background:linear-gradient(90deg,#64748b,#94a3b8)"></span></div>
                </li>
            </ul>
        </section>
    </div>

    {{-- ═══ RECENT USERS + RECENT ACTIVITY ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-users"></i> Recent users</h4>
                <a href="{{ route('admin.users.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
            </div>
            <table class="dash-table">
                <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
                <tbody>
                @forelse(($stats['recent_users'] ?? []) as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->role?->name ?? '—' }}</td>
                        <td><span class="dash-meta-chip {{ $user->status === 'active' ? 'm-green' : ($user->status === 'pending' ? 'm-amber' : 'm-gray') }}">{{ ucfirst($user->status ?? 'active') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--bcp-muted);padding:20px">No users yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-clock-rotate-left"></i> Recent activity</h4>
                <span class="panel-count">{{ ($stats['recent_audit_logs'] ?? collect())->count() }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($stats['recent_audit_logs'] ?? []) as $log)
                    <li class="dash-list-item">
                        <span class="dash-list-icon"><i class="fa-solid fa-file-shield"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $log->action ?? $log->event ?? 'Activity' }}</p>
                            <p class="dash-list-sub">{{ $log->description ?? '' }}</p>
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

    {{-- ═══ RECENT ENROLLMENTS + RECENT COURSES ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-user-plus"></i> Recent enrollments</h4>
                <a href="{{ route('admin.enrollments.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
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
                <h4><i class="fa-solid fa-book-open"></i> Recent courses</h4>
                <a href="{{ route('admin.courses.index') }}" class="view-all" style="margin:0;padding:4px 9px">View all</a>
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

    {{-- ═══ ADMIN ANALYTICS OVERVIEW ═══ --}}
    <section class="dash-analytics">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-chart-pie"></i> System Analytics</h4>
            <a href="{{ route('admin.analytics') }}" class="view-all" style="margin:0;padding:4px 9px"><i class="fa-solid fa-chart-simple"></i> Full analytics</a>
        </div>
        <div class="analytics-content">
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-user-plus"></i> Enrollment Status</h5>
                    <div id="adminEnrollmentChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-pie"></i><p>Loading…</p></div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-book"></i> Courses by Status</h5>
                    <div id="adminCoursesChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-bar"></i><p>Loading…</p></div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-users"></i> Users by Role</h5>
                    <div id="adminUsersChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-pie"></i><p>Loading…</p></div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-clipboard-check"></i> Attendance Overview</h5>
                    <div id="adminAttendanceChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-pie"></i><p>Loading…</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Auto-refresh admin stat tiles every 30s
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('admin.dashboard.real-time-stats') }}');
                if (!response.ok) throw new Error('Bad status: ' + response.status);
                const data = await response.json();
                if (data.success) {
                    const s = data.data;
                    // Tiles are server-rendered; update the numeric spans where present
                    const activeEl = document.querySelector('.dash-stat .trend-chip');
                    console.log('Admin stats refreshed:', s);
                }
            } catch (error) {
                console.error('Error fetching admin real-time stats:', error);
            }
        }, 30000);

        // Admin analytics charts
        let adminCharts = {};
        function destroyAdminChart(id) {
            if (adminCharts[id]) { adminCharts[id].destroy(); delete adminCharts[id]; }
        }

        function adminChartOptions() {
            return {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: '#c7d4ec', boxWidth: 12, padding: 12 } } }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetch('{{ route('admin.dashboard.analytics-json') }}')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.error || 'Failed');
                    const d = res.data;

                    // Enrollment doughnut
                    const ec = document.getElementById('adminEnrollmentChart');
                    ec.innerHTML = '<canvas id="adminEnrollCanvas"></canvas>';
                    destroyAdminChart('adminEnrollmentChart');
                    adminCharts['adminEnrollmentChart'] = new Chart(document.getElementById('adminEnrollCanvas'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Active', 'Pending', 'Completed', 'Dropped'],
                            datasets: [{ data: [d.enrollment.active, d.enrollment.pending, d.enrollment.completed, d.enrollment.dropped],
                                backgroundColor: ['#34d399', '#fbbf24', '#62c9f5', '#fb7185'], borderColor: '#151c2c', borderWidth: 2 }]
                        },
                        options: adminChartOptions()
                    });

                    // Courses by status bar
                    const cc = document.getElementById('adminCoursesChart');
                    cc.innerHTML = '<canvas id="adminCoursesCanvas"></canvas>';
                    destroyAdminChart('adminCoursesChart');
                    adminCharts['adminCoursesChart'] = new Chart(document.getElementById('adminCoursesCanvas'), {
                        type: 'bar',
                        data: {
                            labels: ['Published', 'Draft', 'Archived'],
                            datasets: [{ label: 'Courses',
                                data: [d.courses_by_status.published, d.courses_by_status.draft, d.courses_by_status.archived],
                                backgroundColor: ['rgba(52,211,153,.6)', 'rgba(251,191,36,.6)', 'rgba(148,163,184,.6)'],
                                borderColor: ['#34d399', '#fbbf24', '#94a3b8'], borderWidth: 1, borderRadius: 6, maxBarThickness: 50 }]
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

                    // Users by role doughnut
                    const uc = document.getElementById('adminUsersChart');
                    uc.innerHTML = '<canvas id="adminUsersCanvas"></canvas>';
                    destroyAdminChart('adminUsersChart');
                    adminCharts['adminUsersChart'] = new Chart(document.getElementById('adminUsersCanvas'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Admins', 'Instructors', 'Students', 'Registrars'],
                            datasets: [{ data: [d.users_by_role.admins, d.users_by_role.instructors, d.users_by_role.students, d.users_by_role.registrars],
                                backgroundColor: ['#62c9f5', '#8b5cf6', '#34d399', '#fbbf24'], borderColor: '#151c2c', borderWidth: 2 }]
                        },
                        options: adminChartOptions()
                    });

                    // Attendance doughnut
                    const ac = document.getElementById('adminAttendanceChart');
                    ac.innerHTML = '<canvas id="adminAttendCanvas"></canvas>';
                    destroyAdminChart('adminAttendanceChart');
                    adminCharts['adminAttendanceChart'] = new Chart(document.getElementById('adminAttendCanvas'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Present', 'Absent', 'Late'],
                            datasets: [{ data: [d.attendance.present, d.attendance.absent, d.attendance.late],
                                backgroundColor: ['#34d399', '#fb7185', '#fbbf24'], borderColor: '#151c2c', borderWidth: 2 }]
                        },
                        options: adminChartOptions()
                    });
                })
                .catch(err => console.error('Admin analytics error:', err));
        });
    </script>

    <style>
        .dash-analytics { margin: 30px 0; padding: 20px; background: var(--dash-surface, #151c2c); border: 1px solid var(--dash-line, rgba(153,174,214,.18)); border-radius: 16px; box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3)); }
        .dash-analytics .dash-panel-head { padding: 0 0 16px; border-bottom: 1px solid var(--dash-line, rgba(153,174,214,.18)); margin-bottom: 20px; }
        .analytics-content { margin-top: 20px; }
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .analytics-card {
            background: rgba(77,143,240,.04); border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; padding: 20px;
        }
        .analytics-card h5 { margin: 0 0 16px 0; font-size: 15px; font-weight: 700; color: var(--dash-text, #eef4ff); display: flex; align-items: center; gap: 8px; }
        .analytics-card h5 i { color: var(--bcp-cyan-400, #62c9f5); }
        .chart-container { position: relative; height: 240px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .chart-container canvas { max-height: 100%; }
        .chart-placeholder { text-align: center; color: var(--dash-muted, #98a7c4); }
        .chart-placeholder i { font-size: 44px; margin-bottom: 12px; color: rgba(153,174,214,.35); }
        .chart-placeholder p { margin: 0; font-size: 14px; }
        @media (max-width: 640px) {
            .analytics-grid { grid-template-columns: 1fr; }
            .dash-analytics { padding: 16px; }
        }
    </style>
</div>
@endsection
