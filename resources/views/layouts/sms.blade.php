<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @vite(['resources/css/sms-template.css', 'resources/css/app.css', 'resources/css/admin-ui.css', 'resources/css/compact-ui.css', 'resources/css/dashboard-palette.css', 'resources/css/sidebar-polish.css', 'resources/css/user-list-ui.css', 'resources/css/admin-consistency.css', 'resources/css/filter-toolbar-ui.css', 'resources/css/topbar-global-ui.css', 'resources/css/profile-enhancements.css', 'resources/css/course-form-ui.css', 'resources/css/gradebook-ui.css', 'resources/css/role-admin-parity.css', 'resources/css/user-ui-system.css', 'resources/css/lms-polish.css', 'resources/css/sidebar-layout-fix.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="{{ trim((auth()->check() ? 'admin-ui ' : '') . $__env->yieldContent('body-class')) }}">

@if($__env->hasSection('sidebar'))
    @yield('sidebar')
@endif

<div class="main">
    <div class="topbar">
        <button type="button" class="hamburger" id="hamburgerBtn" aria-expanded="true" aria-controls="sidebar" aria-label="Hide sidebar" title="Hide sidebar">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <a class="bcp-topbar-brand" href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
            <img src="{{ asset('images/BCP_LOGO.png') }}" alt="BCP logo">
            <span>Bestlink College of the Philippines</span>
        </a>
        <span class="topbar-spacer"></span>
        <div class="topbar-right">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light theme" title="Toggle theme">
                <i class="fa-solid fa-sun"></i>
            </button>
            @auth
                @if(auth()->user()->isAdmin())
                    <form class="search-wrap" id="globalTopSearch" action="{{ route('admin.search') }}" method="GET" role="search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="topSearchInput" name="search" value="{{ request()->routeIs('admin.search') ? request('search') : '' }}" placeholder="Search courses, classes, lessons..." aria-label="Search courses and classes" autocomplete="off"/>
                        @if(request()->routeIs('admin.search') && request('search'))
                            <a class="search-clear" href="{{ route('admin.search') }}" aria-label="Clear search"><i class="fa-solid fa-xmark"></i></a>
                        @endif
                        <button type="submit" class="search-submit" id="topSearchGo" aria-label="Submit search"><i class="fa-solid fa-arrow-right"></i></button>
                        <div class="top-search-dropdown" id="topSearchResults" hidden></div>
                    </form>
                @else
                    <div class="search-wrap" id="globalTopSearch" role="search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="topSearchInput" placeholder="Search your courses, classes..." aria-label="Search the system" autocomplete="off"/>
                        <button type="button" class="search-submit" id="topSearchGo" aria-label="Submit search"><i class="fa-solid fa-arrow-right"></i></button>
                        <div class="top-search-dropdown" id="topSearchResults" hidden></div>
                    </div>
                @endif
            @endauth
            @auth
                <div class="user-dropdown">
                    <button type="button" class="avatar" id="avatarBtn" title="Open profile menu" aria-label="Open profile menu" aria-haspopup="true" aria-expanded="false">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </button>
                    <div class="user-menu" id="userMenu" role="menu">
                        <div class="dropdown-header">
                            <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="user-email">{{ auth()->user()->email ?? '' }}</div>
                            <small>{{ auth()->user()->role?->name ?? auth()->user()->role?->slug ?? 'User' }}</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item" role="menuitem">
                            <i class="fa-solid fa-user-pen"></i>
                            Edit profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item dropdown-item-button" role="menuitem">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>

    <div class="content">
        @if($__env->hasSection('page-title-bar'))
            @yield('page-title-bar')
        @else
            <div class="page-title-bar">
                <h2 class="page-title">
                    @if(isset($pageTitle))
                        {!! $pageIcon ?? '' !!}
                        {{ $pageTitle }}
                    @else
                        @yield('title', 'Dashboard')
                    @endif
                </h2>
                @if($__env->hasSection('page-actions'))
                    <div class="page-actions">@yield('page-actions')</div>
                @endif
            </div>
        @endif
        
        @if (session('success'))
            <div class="toast success show">
                <div class="toast-label">
                    <div class="toast-dot"></div>
                    <span>Success</span>
                </div>
                <div class="toast-msg">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('status'))
            <div class="toast success show">
                <div class="toast-label">
                    <div class="toast-dot"></div>
                    <span>Success</span>
                </div>
                <div class="toast-msg">{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="toast error show">
                <div class="toast-label">
                    <div class="toast-dot"></div>
                    <span>Error</span>
                </div>
                <div class="toast-msg">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="toast error show">
                <div class="toast-label">
                    <div class="toast-dot"></div>
                    <span>Error</span>
                </div>
                <div class="toast-msg">{{ implode(', ', $errors->all()) }}</div>
            </div>
        @endif

        @yield('content')
    </div>
    
</div>

<!-- Sidebar tap-outside overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

@yield('modals')
@yield('notifications')

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileQuery = window.matchMedia('(max-width: 900px)');

    const syncSidebarState = function() {
        if (!sidebar) return;

        const isMobile = mobileQuery.matches;
        const isSidebarOpen = !sidebar.classList.contains('collapsed');

        // Mobile uses a fixed drawer; desktop uses a width-collapsed rail.
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active', isMobile && isSidebarOpen);
        }

        if (hamburgerBtn) {
            hamburgerBtn.setAttribute('aria-expanded', isSidebarOpen ? 'true' : 'false');
            hamburgerBtn.setAttribute('aria-label', isSidebarOpen ? 'Hide sidebar' : 'Show sidebar');
            hamburgerBtn.setAttribute('title', isSidebarOpen ? 'Hide sidebar' : 'Show sidebar');
        }
    };

    const syncSidebarForViewport = function() {
        if (!sidebar) return;

        // Start mobile layouts closed so the drawer never squeezes the page.
        sidebar.classList.toggle('collapsed', mobileQuery.matches);
        syncSidebarState();
    };

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            syncSidebarState();
        });
        window.addEventListener('resize', syncSidebarForViewport);
        syncSidebarForViewport();
    }

    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.add('collapsed');
            syncSidebarState();
        });
    }

    // Keep page-level actions reachable after the hero scrolls out of view.
    const userHero = document.querySelector('.user-hero');
    const userHeroActions = userHero ? userHero.querySelector('.user-hero-actions') : null;
    if (userHero && userHeroActions && 'IntersectionObserver' in window) {
        const userHeroObserver = new IntersectionObserver(function(entries) {
            const heroVisible = entries[0] && entries[0].isIntersecting;
            document.body.classList.toggle('user-hero-scrolled', !heroVisible);
        }, { threshold: 0, rootMargin: '-48px 0px 0px 0px' });

        userHeroObserver.observe(userHero);
    }

    // Collapsible sidebar groups: keep the active section visible and remember user preference.
    const sidebarGroups = document.querySelectorAll('.sidebar-nav .nav-group');
    sidebarGroups.forEach(function(group) {
        const label = group.querySelector('.nav-group-label');
        const groupKey = label ? label.dataset.sidebarGroup : null;
        const hasActiveItem = group.querySelector('.nav-item.active');
        const savedState = groupKey ? localStorage.getItem('bcp-sidebar-group-' + groupKey) : null;
        const open = hasActiveItem || savedState !== 'collapsed';
        group.classList.toggle('is-collapsed', !open);
        if (label) label.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (label) {
            label.addEventListener('click', function() {
                const collapsed = group.classList.toggle('is-collapsed');
                label.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                if (groupKey) localStorage.setItem('bcp-sidebar-group-' + groupKey, collapsed ? 'collapsed' : 'open');
            });
            label.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    label.click();
                }
            });
        }
    });

    // Prevent duplicate submissions and give every POST/PUT/DELETE form immediate feedback.
    document.querySelectorAll('form').forEach(function(form) {
        if (form.dataset.noLoading === 'true' || (form.method || 'get').toLowerCase() === 'get') return;
        form.addEventListener('submit', function() {
            const submit = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submit || submit.disabled) return;
            submit.disabled = true;
            submit.classList.add('is-loading');
            submit.dataset.originalLabel = submit.innerHTML || submit.value || '';
            if (submit.tagName === 'BUTTON') {
                submit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Processing...</span>';
            } else {
                submit.value = 'Processing...';
            }
        });
    });

    // Auto-hide flash toasts after a few seconds (kept from the legacy SMS template).
    const flashToasts = document.querySelectorAll('.toast.show');
    flashToasts.forEach(function(t) {
        window.setTimeout(function() { t.classList.remove('show'); }, 4200);
    });

    const avatarBtn = document.getElementById('avatarBtn');
    const userMenu = document.getElementById('userMenu');
    if (avatarBtn && userMenu) {
        const setMenuOpen = function(open) {
            userMenu.classList.toggle('active', open);
            avatarBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        avatarBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            setMenuOpen(!userMenu.classList.contains('active'));
        });
        document.addEventListener('click', function(event) {
            if (!avatarBtn.contains(event.target) && !userMenu.contains(event.target)) setMenuOpen(false);
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') setMenuOpen(false);
        });
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;
    function syncThemeIcon() {
        if (!themeIcon) return;
        const isLight = document.body.classList.contains('light-mode');
        themeIcon.className = isLight ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        themeToggle.setAttribute('aria-label', isLight ? 'Use dark theme' : 'Use light theme');
    }
    if (localStorage.getItem('bcp-theme') === 'light') document.body.classList.add('light-mode');
    syncThemeIcon();
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('bcp-theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
            syncThemeIcon();
        });
    }

    // Shared list filtering: select/date controls apply immediately, while text search
    // submits on Enter or after a short pause. Existing Apply/Filter buttons still work.
    const filterForms = document.querySelectorAll('form.user-toolbar, form.table-toolbar, form.filter-form, form[data-filter-form]');
    filterForms.forEach(function(form) {
        if ((form.method || 'get').toLowerCase() !== 'get') return;
        if (form.dataset.autoFilterReady === 'true') return;
        form.dataset.autoFilterReady = 'true';

        const submit = function() {
            if (form.dataset.filterSubmitting === 'true') return;
            form.dataset.filterSubmitting = 'true';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        form.querySelectorAll('select, input[type="date"], input[type="month"]').forEach(function(control) {
            control.addEventListener('change', submit);
        });

        form.querySelectorAll('input[type="search"], input[name="search"]').forEach(function(control) {
            let timer;
            control.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(timer);
                    submit();
                }
            });
            control.addEventListener('input', function() {
                window.clearTimeout(timer);
                timer = window.setTimeout(function() {
                    if (document.activeElement === control && control.value.trim().length === 0) {
                        submit();
                        return;
                    }
                    if (control.value.trim().length >= 2) submit();
                }, 550);
            });
        });
    });
});
</script>

@stack('scripts')

{{-- Topbar live search for non-admin roles (instructor / student) --}}
@auth
@if(!auth()->user()->isAdmin())
<script>
@php
    $searchEndpoint = match (auth()->user()->role?->slug) {
        'instructor' => route('instructor.dashboard.search'),
        default => route('student.dashboard.search'),
    };
@endphp
(function () {
    'use strict';
    var searchBox = document.getElementById('globalTopSearch');
    if (!searchBox) return;

    var input = document.getElementById('topSearchInput');
    var goBtn = document.getElementById('topSearchGo');
    var panel = document.getElementById('topSearchResults');
    var role = '{{ auth()->user()->role?->slug }}';
    var endpoint = '{{ $searchEndpoint }}';
    var debounceTimer = null;
    var controller = null;
    var lastQuery = '';

    var TYPE_META = {
        students:      { label: 'Student',      icon: 'fa-user-graduate',  cls: 'tb-student' },
        enrollments:   { label: 'Enrollment',   icon: 'fa-user-plus',      cls: 'tb-enrollment' },
        courses:       { label: 'Course',       icon: 'fa-book',           cls: 'tb-course' },
        classes:       { label: 'Class',        icon: 'fa-school',         cls: 'tb-class' },
        modules:       { label: 'Module',       icon: 'fa-layer-group',    cls: 'tb-module' },
        lessons:       { label: 'Lesson',       icon: 'fa-book-open-reader', cls: 'tb-lesson' },
        assignments:   { label: 'Assignment',   icon: 'fa-tasks',          cls: 'tb-assignment' },
        quizzes:       { label: 'Quiz',         icon: 'fa-question-circle',cls: 'tb-quiz' },
        grades:        { label: 'Grade',        icon: 'fa-graduation-cap', cls: 'tb-grade' },
    };

    function inferType(r) {
        // Explicit entity markers from the new search categories take priority.
        if (r.entity === 'module') return 'modules';
        if (r.entity === 'lesson') return 'lessons';
        if (r.entity === 'grade') return 'grades';

        if (role === 'instructor') {
            if (r.email) return 'students';
            if (r.class_code && r.due_date) return 'assignments';
            if (r.class_code && r.availability_from) return 'quizzes';
            if (r.class_code && r.course_title && r.name) return 'enrollments';
            if (r.course_title && r.code) return 'classes';
            if (r.title) return 'courses';
        } else { // student
            if (r.due_date) return 'assignments';
            if (r.availability_from) return 'quizzes';
            return 'courses';
        }
        return 'courses';
    }

    function hrefFor(r, type) {
        if (!r.id) return '#';
        if (role === 'instructor') {
            if (type === 'students') return '{{ url("instructor/enrollments") }}';
            if (type === 'courses') return '{{ url("instructor/courses") }}/' + r.id;
            if (type === 'classes') return '{{ url("instructor/classes") }}/' + r.id;
            return '{{ url("instructor/classes") }}';
        }
        // student
        if (type === 'courses') return '{{ url("student/courses") }}/' + r.id;
        return '{{ url("student/courses") }}';
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showLoading() {
        panel.hidden = false;
        panel.innerHTML = '<div class="ts-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching&hellip;</div>';
    }

    function render(data) {
        if (!data || !data.data || data.data.length === 0) {
            panel.innerHTML = '<div class="ts-empty">No results found for &ldquo;' + esc(lastQuery) + '&rdquo;</div>';
            panel.hidden = false;
            return;
        }

        var seen = {};
        var html = '<div class="tsd-head"><i class="fa-solid fa-magnifying-glass"></i> Results for &ldquo;' + esc(lastQuery) + '&rdquo;</div>';
        var items = 0;

        data.data.forEach(function (r) {
            if (items >= 12) return;
            var type = inferType(r);
            if (seen[type + ':' + r.id]) return;
            seen[type + ':' + r.id] = true;

            var meta = TYPE_META[type] || TYPE_META.courses;
            var title = r.name || r.title || r.code || '';
            var subParts = [];
            if (r.email) subParts.push(r.email);
            if (r.course_title) subParts.push(r.course_title);
            if (r.class_code) subParts.push(r.class_code);
            if (!r.email && !r.course_title && !r.class_code && r.code) {
                subParts.push(r.code + (r.status ? ' · ' + r.status : ''));
            }
            var sub = subParts.join(' · ');

            html += '<a class="tsd-item" href="' + hrefFor(r, type) + '">' +
                '<span class="tsd-title"><span>' + esc(title) + '</span><span class="ts-badge ' + meta.cls + '"><i class="fa-solid ' + meta.icon + '"></i> ' + meta.label + '</span></span>' +
                (sub ? '<span class="tsd-sub">' + esc(sub) + '</span>' : '') +
                '</a>';
            items++;
        });

        if (items === 0) {
            panel.innerHTML = '<div class="ts-empty">No results found for &ldquo;' + esc(lastQuery) + '&rdquo;</div>';
        } else {
            html += '<div class="ts-footer">' + items + ' of ' + data.data.length + ' matches &middot; press Enter to search all</div>';
            panel.innerHTML = html;
        }
        panel.hidden = false;
    }

    function doSearch(query) {
        if (query.length < 2) {
            panel.hidden = true;
            return;
        }
        lastQuery = query;
        showLoading();

        if (controller) controller.abort();
        controller = new AbortController();

        fetch(endpoint + '?query=' + encodeURIComponent(query) + '&type=all', {
            signal: controller.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) render(data);
                else { panel.innerHTML = '<div class="ts-empty">Search failed. Try again.</div>'; panel.hidden = false; }
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                panel.innerHTML = '<div class="ts-empty">Search unavailable right now.</div>';
                panel.hidden = false;
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = this.value.trim();
        if (q.length < 2) { panel.hidden = true; return; }
        debounceTimer = setTimeout(function () { doSearch(q); }, 300);
    });

    goBtn.addEventListener('click', function () {
        var q = input.value.trim();
        if (q.length >= 2) doSearch(q);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var q = this.value.trim();
            if (q.length >= 2) doSearch(q);
        }
        if (e.key === 'Escape') { panel.hidden = true; this.blur(); }
    });

    document.addEventListener('click', function (e) {
        if (!searchBox.contains(e.target)) panel.hidden = true;
    });
})();
</script>
@endif
@endauth

@include('components.inactivity-watchdog')
</body>
</html>
