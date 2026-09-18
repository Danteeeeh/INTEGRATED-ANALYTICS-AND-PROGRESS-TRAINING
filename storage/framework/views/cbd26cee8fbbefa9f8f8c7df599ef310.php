<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="<?php echo e(asset('images/BCP_LOGO.png')); ?>" alt="BCP logo"><div><h2><?php echo e(config('app.name')); ?></h2>
        <span class="sidebar-subtitle">Admin Panel</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'dashboard' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-link" <?php if($activeNav === 'dashboard'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="people" aria-expanded="true">People</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'users' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="nav-link" <?php if($activeNav === 'users'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'students' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.students.index')); ?>" class="nav-link" <?php if($activeNav === 'students'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'instructors' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.instructors.index')); ?>" class="nav-link" <?php if($activeNav === 'instructors'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <span>Instructors</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academic" aria-expanded="true">Academic</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'courses' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.courses.index')); ?>" class="nav-link" <?php if($activeNav === 'courses'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'classes' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.classes.index')); ?>" class="nav-link" <?php if($activeNav === 'classes'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-school"></i>
                        <span>Classes</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e(in_array($activeNav, ['enrollment', 'enrollments'], true) ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="nav-link" <?php if(in_array($activeNav, ['enrollment', 'enrollments'], true)): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'academic_periods' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.academic_periods.index')); ?>" class="nav-link" <?php if($activeNav === 'academic_periods'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-calendar"></i>
                        <span>Academic Periods</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="assessment" aria-expanded="true">Assessment</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'assignments' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.assignments.index')); ?>" class="nav-link" <?php if($activeNav === 'assignments'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'quizzes' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.quizzes.index')); ?>" class="nav-link" <?php if($activeNav === 'quizzes'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'gradebook' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.gradebook.index')); ?>" class="nav-link" <?php if($activeNav === 'gradebook'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Gradebook</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'reports' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.reports.index')); ?>" class="nav-link" <?php if($activeNav === 'reports'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'settings' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.settings.index')); ?>" class="nav-link nav-link--settings" title="System settings" <?php if($activeNav === 'settings'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'audit_logs' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('admin.audit_logs.index')); ?>" class="nav-link" <?php if($activeNav === 'audit_logs'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside><?php /**PATH C:\xampp\htdocs\lms\resources\views/components/admin-sidebar.blade.php ENDPATH**/ ?>