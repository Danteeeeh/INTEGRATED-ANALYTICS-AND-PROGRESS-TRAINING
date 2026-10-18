import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/sms-template.css',
                'resources/css/admin-ui.css',
                'resources/css/compact-ui.css',
                'resources/css/dashboard-palette.css',
                'resources/css/dashboard-layout.css',
                'resources/css/sidebar-polish.css',
                'resources/css/user-list-ui.css',
                'resources/css/admin-consistency.css',
                'resources/css/filter-toolbar-ui.css',
                'resources/css/topbar-global-ui.css',
                'resources/css/profile-enhancements.css',
                'resources/css/course-form-ui.css',
                'resources/css/gradebook-ui.css',
                'resources/css/role-admin-parity.css',
                'resources/css/user-ui-system.css',
                'resources/css/student-progress-ui.css',
                'resources/css/lms-polish.css',
                'resources/css/sidebar-layout-fix.css',
                // Built-in Virtual Classroom (LiveKit) — bundled so the room
                // needs neither a CDN script nor an inline <script>/<style>.
                'resources/css/virtual-classroom.css',
                'resources/js/virtual-classroom.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
    },
});
