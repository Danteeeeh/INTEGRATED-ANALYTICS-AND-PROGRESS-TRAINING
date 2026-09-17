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
                'resources/css/lms-polish.css',
            ],
            refresh: true,
        }),
    ],
});
