/**
 * Vanilla JS entry point. Feature-specific scripts (quiz timer, rich text
 * editor, file upload preview, etc.) are added module-by-module in later
 * phases and imported here.
 */

function toast(message, type = 'info', timeout = 4000) {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const colors = {
        success: 'bg-emerald-600',
        error: 'bg-red-600',
        info: 'bg-slate-800',
        warning: 'bg-amber-600',
    };

    const el = document.createElement('div');
    el.className = `${colors[type] ?? colors.info} text-white text-sm px-4 py-2.5 rounded-lg shadow-lg`;
    el.textContent = message;
    root.appendChild(el);

    setTimeout(() => el.remove(), timeout);
}

// Mobile menu toggle functionality
function initMobileMenu() {
    const menuButton = document.querySelector('button[aria-label="Open menu"]');
    const sidebar = document.querySelector('aside');
    
    if (!menuButton || !sidebar) return;

    const toggleSidebar = () => {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('fixed');
        sidebar.classList.toggle('inset-0');
        sidebar.classList.toggle('z-50');
        
        // Update aria label for accessibility
        const isOpen = !sidebar.classList.contains('hidden');
        menuButton.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        menuButton.setAttribute('aria-expanded', isOpen);
    };

    menuButton.addEventListener('click', toggleSidebar);

    // Add keyboard support
    menuButton.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleSidebar();
        }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 1024 && // lg breakpoint
            !sidebar.contains(e.target) && 
            !menuButton.contains(e.target) &&
            !sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
            sidebar.classList.remove('fixed', 'inset-0', 'z-50');
            menuButton.setAttribute('aria-label', 'Open menu');
            menuButton.setAttribute('aria-expanded', 'false');
        }
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
            sidebar.classList.remove('fixed', 'inset-0', 'z-50');
            menuButton.setAttribute('aria-label', 'Open menu');
            menuButton.setAttribute('aria-expanded', 'false');
        }
    });
}

// Notification button functionality
function initNotifications() {
    const notificationButton = document.querySelector('button[aria-label="Notifications"]');
    if (!notificationButton) return;

    notificationButton.addEventListener('click', () => {
        toast('Notifications will be available in Phase 6', 'info');
    });

    // Add keyboard support
    notificationButton.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            notificationButton.click();
        }
    });
}

// Placeholder sidebar items functionality
function initSidebarPlaceholders() {
    const sidebarLinks = document.querySelectorAll('nav a[href="#"]');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const label = link.querySelector('span:last-child')?.textContent || 'This feature';
            toast(`${label} will be available in later phases`, 'info');
        });
    });
}

// Login form functionality
function initLoginForm() {
    const loginForm = document.querySelector('form[method="POST"]');
    if (!loginForm) return;

    const submitButton = loginForm.querySelector('button[type="submit"]');
    if (!submitButton) return;

    loginForm.addEventListener('submit', () => {
        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Signing in...';
        submitButton.classList.add('opacity-75', 'cursor-not-allowed');
    });
}

// Initialize all functionality when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initNotifications();
    initSidebarPlaceholders();
    initLoginForm();
});

window.LMS = { toast };
