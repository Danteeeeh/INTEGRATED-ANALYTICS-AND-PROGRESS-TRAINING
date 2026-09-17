<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/css/sms-template.css', 'resources/css/admin-ui.css', 'resources/css/compact-ui.css', 'resources/css/dashboard-palette.css', 'resources/css/sidebar-polish.css', 'resources/css/user-list-ui.css', 'resources/css/admin-consistency.css', 'resources/css/filter-toolbar-ui.css', 'resources/css/topbar-global-ui.css', 'resources/css/profile-enhancements.css', 'resources/css/course-form-ui.css', 'resources/css/gradebook-ui.css', 'resources/css/role-admin-parity.css', 'resources/js/app.js'])
    <script src="{{ asset('js/sms-template.js') }}" defer></script>
    @stack('styles')
    <style>
        /* Theme toggle button — flush with unified topbar */
        .theme-toggle { display:inline-flex !important; align-items:center !important; justify-content:center !important; width:30px !important; height:30px !important; padding:0 !important; border-radius:8px !important; cursor:pointer !important; flex:none !important; }
    </style>
</head>
<body class="{{ auth()->check() && auth()->user()->isAdmin() ? 'admin-ui' : '' }}">
    <script>
        /* Apply saved theme before paint so SMS pages never flash the wrong mode */
        (function () {
            if (localStorage.getItem('bcp-theme') === 'light') {
                document.body.classList.add('light-mode');
            }
        })();
    </script>

@if($__env->hasSection('sidebar'))
    @yield('sidebar')
@endif

<div class="main">
    <div class="topbar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="topbar-spacer"></span>
        <div class="topbar-right">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light theme" title="Toggle theme">
                <i class="fa-solid fa-sun"></i>
            </button>
            <div class="search-wrap">
                <input type="text" placeholder="Search..."/>
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            @auth
                <div class="user-dropdown">
                    <a href="#" class="avatar" id="avatarBtn" title="Account Settings">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </a>
                    <div class="dropdown-menu user-menu" id="userMenu">
                        <div class="dropdown-header">
                            <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="user-email">{{ auth()->user()->email ?? '' }}</div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fa-solid fa-user"></i>
                            Profile
                        </a>
                        <a href="#" class="dropdown-item">
                            <i class="fa-solid fa-cog"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item dropdown-item-button">
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
    
    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });
    }
    
    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.add('collapsed');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Theme toggle — unified with main app layout (bcp-theme + body.light-mode)
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const body = document.body;
        if (localStorage.getItem('bcp-theme') === 'light') body.classList.add('light-mode');
        const syncIcon = () => {
            themeToggle.setAttribute('aria-label', body.classList.contains('light-mode') ? 'Use dark theme' : 'Use light theme');
        };
        syncIcon();
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('light-mode');
            localStorage.setItem('bcp-theme', body.classList.contains('light-mode') ? 'light' : 'dark');
            syncIcon();
        });
    }
});
</script>
