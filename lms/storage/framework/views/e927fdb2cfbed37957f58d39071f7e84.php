<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="<?php echo e(asset('images/BCP_LOGO.png')); ?>" alt="BCP logo"><div><h2><?php echo e(config('app.name')); ?></h2>
        <span class="sidebar-subtitle">Registrar / Staff</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'dashboard' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.dashboard')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'dashboard'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="academic" aria-expanded="true">Academic</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'students' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.students.index')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'students'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-user-graduate"></i><span>Students</span></a>
                </li>
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'enrollments' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.enrollments.index')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'enrollments'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-user-plus"></i><span>Enrollments</span></a>
                </li>
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'classes' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.classes.index')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'classes'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-school"></i><span>Classes</span></a>
                </li>
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'courses' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.courses.index')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'courses'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-book"></i><span>Courses</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="system" aria-expanded="true">System</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e(($activeNav ?? '') === 'reports' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('registrar.reports.index')); ?>" class="nav-link" <?php if(($activeNav ?? '') === 'reports'): ?> aria-current="page" <?php endif; ?>><i class="fa-solid fa-chart-bar"></i><span>Reports</span></a>
                </li>
            </ul>
        </div>
    </nav>
</aside><?php /**PATH C:\xampp\htdocs\lms\resources\views/components/registrar-sidebar.blade.php ENDPATH**/ ?>