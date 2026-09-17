<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    @vite(['resources/css/sms-template.css', 'resources/css/app.css', 'resources/css/admin-ui.css', 'resources/css/compact-ui.css', 'resources/css/dashboard-palette.css', 'resources/css/sidebar-polish.css', 'resources/css/user-list-ui.css', 'resources/css/admin-consistency.css', 'resources/css/filter-toolbar-ui.css', 'resources/css/topbar-global-ui.css', 'resources/css/profile-enhancements.css', 'resources/css/course-form-ui.css', 'resources/css/gradebook-ui.css', 'resources/css/role-admin-parity.css', 'resources/css/user-ui-system.css', 'resources/css/lms-polish.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="{{ trim((auth()->check() ? 'admin-ui ' : '') . $__env->yieldContent('body-class')) }}">

@if($__env->hasSection('sidebar'))
    @yield('sidebar')
@endif

<div class="main">
    <div class="topbar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
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
                    <form class="search-wrap" action="{{ route('admin.search') }}" method="GET" role="search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search courses, classes..." aria-label="Search courses and classes" autocomplete="off"/>
                        @if(request('search'))
                            <a class="search-clear" href="{{ url()->current() }}" aria-label="Clear search"><i class="fa-solid fa-xmark"></i></a>
                        @endif
                        <button type="submit" class="search-submit" aria-label="Submit search"><i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                @else
                    <div class="search-wrap search-wrap-disabled" title="Search is available for administrators">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" placeholder="Search..." aria-label="Search unavailable for this role" disabled/>
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
    
    const syncSidebarOverlay = function() {
        if (!sidebarOverlay || !sidebar) return;
        const isMobile = window.matchMedia('(max-width: 900px)').matches;
        const isSidebarOpen = !sidebar.classList.contains('collapsed');
        // Blur the page only while the mobile drawer is open.
        sidebarOverlay.classList.toggle('active', isMobile && isSidebarOpen);
    };

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            syncSidebarOverlay();
        });
        window.addEventListener('resize', syncSidebarOverlay);
        syncSidebarOverlay();
    }
    
    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.add('collapsed');
            syncSidebarOverlay();
        });
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
    if (localStorage.getItem('bcp-theme') === 'light') document.body.classList.add('light-mode');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('bcp-theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
            themeToggle.setAttribute('aria-label', document.body.classList.contains('light-mode') ? 'Use dark theme' : 'Use light theme');
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
</body>
</html>
