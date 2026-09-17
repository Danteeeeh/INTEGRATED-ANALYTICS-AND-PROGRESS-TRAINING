<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-lockup"><img src="<?php echo e(asset('images/BCP_LOGO.png')); ?>" alt="BCP logo"><div><h2><?php echo e(config('app.name')); ?></h2>
        <span class="sidebar-subtitle">Student Portal</span></div></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="main" aria-expanded="true">Main</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'dashboard' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.dashboard')); ?>" class="nav-link" <?php if($activeNav === 'dashboard'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="learning" aria-expanded="true">Learning</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'courses' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.courses.index')); ?>" class="nav-link" <?php if($activeNav === 'courses'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'enrollments' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.enrollments.index')); ?>" class="nav-link" <?php if($activeNav === 'enrollments'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Enrollments</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'gradebook' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.classes.index')); ?>" class="nav-link" <?php if($activeNav === 'gradebook'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>My Grades</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'virtual_classes' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.classes.index')); ?>" class="nav-link" <?php if($activeNav === 'virtual_classes'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-video"></i>
                        <span>Virtual Classes</span>
                        <span class="link-hint">Pick class</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="courses" aria-expanded="true">In your courses</button>
            <ul class="nav-list">
                <li class="nav-item <?php echo e($activeNav === 'assignments' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.courses.index')); ?>" class="nav-link" <?php if($activeNav === 'assignments'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-tasks"></i>
                        <span>Assignments</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'quizzes' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.courses.index')); ?>" class="nav-link" <?php if($activeNav === 'quizzes'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-question-circle"></i>
                        <span>Quizzes</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e($activeNav === 'discussions' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('student.courses.index')); ?>" class="nav-link" <?php if($activeNav === 'discussions'): ?> aria-current="page" <?php endif; ?>>
                        <i class="fa-solid fa-comments"></i>
                        <span>Discussions</span>
                        <span class="link-hint">Pick course</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <button type="button" class="nav-group-label" data-sidebar-group="coming-soon" aria-expanded="true">Coming soon</button>
            <ul class="nav-list">
                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>My Progress</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-certificate"></i>
                        <span>Certificates</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-medal"></i>
                        <span>Badges</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>

                <li class="nav-item disabled">
                    <div class="nav-link">
                        <i class="fa-solid fa-calendar"></i>
                        <span>Calendar</span>
                        <span class="soon-badge">Soon</span>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</aside><?php /**PATH C:\xampp\htdocs\lms\resources\views\components\student-sidebar.blade.php ENDPATH**/ ?>