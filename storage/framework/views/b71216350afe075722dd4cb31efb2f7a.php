<?php $__env->startSection('title', $course->name); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = $course->name;
    $pageIcon = '<i class="fa-solid fa-book"></i>';
    $tabs = ['modules', 'lessons', 'assignments', 'quizzes', 'discussions', 'announcements'];
    $activeTab = request('tab', 'modules');
    $modules = $course->modules ?? collect();
    $lessons = $modules->flatMap->lessons;
    $assignments = $assignments ?? collect();
    $quizzes = $quizzes ?? collect();
    $discussions = $course->discussions ?? collect();
    $announcements = $course->announcements ?? collect();
    $publishedModules = $modules->where('is_published', true)->count();
    $tabCounts = [
        'modules' => $modules->count(),
        'lessons' => $lessons->count(),
        'assignments' => $assignments->count(),
        'quizzes' => $quizzes->count(),
        'discussions' => $discussions->count(),
        'announcements' => $announcements->count(),
    ];
    $tabIcons = [
        'modules' => 'fa-layer-group',
        'lessons' => 'fa-book-open',
        'assignments' => 'fa-file-pen',
        'quizzes' => 'fa-circle-question',
        'discussions' => 'fa-comments',
        'announcements' => 'fa-bullhorn',
    ];
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book"></i>
            <?php echo e($course->code); ?> — <?php echo e($course->name); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.edit', $course)); ?>" class="dash-action" style="padding: 9px 16px; font-size: .78rem;">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Course
            </a>
            <a href="<?php echo e(route('instructor.courses.index')); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Courses
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
    .course-hero {
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: space-between; gap: 24px;
        padding: 26px 28px; margin-bottom: 22px;
        border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 18px;
        background:
            radial-gradient(circle at 88% 10%, rgba(77,143,240,.35), transparent 42%),
            radial-gradient(circle at 60% 120%, rgba(98,201,245,.14), transparent 45%),
            linear-gradient(135deg, rgba(23,58,168,.9), rgba(10,16,32,.97));
        box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
    }
    .course-hero:before {
        content: ""; position: absolute; inset: auto -70px -120px auto;
        width: 300px; height: 220px; border-radius: 50%;
        background: rgba(98,201,245,.12); filter: blur(8px); pointer-events: none;
    }
    .ch-main { position: relative; z-index: 1; min-width: 0; }
    .ch-kicker {
        display: block; color: var(--bcp-cyan-400, #62c9f5);
        font-size: .66rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; margin-bottom: 8px;
    }
    .ch-main h1 { margin: 0; color: #fff; font-size: clamp(1.3rem, 2.4vw, 1.9rem); letter-spacing: -.03em; }
    .ch-main p { margin: 8px 0 0; max-width: 640px; color: #d4e2fa; font-size: .85rem; line-height: 1.55; }
    .ch-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
    .ch-chip {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 5px 12px; border-radius: 999px;
        background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.16);
        color: #dbeafe; font-size: .72rem; font-weight: 700;
    }
    .ch-stats {
        position: relative; z-index: 1; flex: none;
        display: grid; grid-template-columns: repeat(2, minmax(80px, 1fr)); gap: 10px;
    }
    .ch-stat {
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        padding: 12px 16px; border-radius: 12px;
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
        backdrop-filter: blur(6px);
    }
    .ch-stat-value { color: #fff; font-size: 1.35rem; font-weight: 800; }
    .ch-stat-label { color: #9db0d1; font-size: .64rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }

    /* Tabs */
    .course-tabs {
        display: flex; gap: 4px; flex-wrap: wrap;
        border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
        margin: 0 0 22px; padding: 0 4px;
    }
    .course-tab {
        position: relative;
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 18px;
        color: var(--bcp-muted, #98a7c4);
        font-size: .82rem; font-weight: 700; text-decoration: none;
        border-bottom: 3px solid transparent; margin-bottom: -1px;
        transition: color .18s, background .18s, border-color .18s;
        border-radius: 10px 10px 0 0;
    }
    .course-tab i { color: var(--bcp-muted, #98a7c4); font-size: .8rem; transition: color .18s; }
    .course-tab:hover { color: var(--bcp-cyan-400, #62c9f5); background: rgba(77,143,240,.08); }
    .course-tab:hover i { color: var(--bcp-cyan-400, #62c9f5); }
    .course-tab.active { color: #fff; border-bottom-color: #4d8ff0; background: linear-gradient(180deg, rgba(77,143,240,.14), transparent); }
    .course-tab.active i { color: #62c9f5; }
    .tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 20px; height: 20px; padding: 0 6px;
        border-radius: 999px;
        background: rgba(77,143,240,.16); color: var(--bcp-cyan-400, #62c9f5);
        font-size: .64rem; font-weight: 800;
    }
    .course-tab.active .tab-badge { background: #4d8ff0; color: #fff; }

    /* Shared tab content base */
    .tab-content-area .crud-card { margin: 0 0 24px; }
    .tab-empty-state { text-align: center; padding: 44px 24px; color: var(--bcp-muted, #98a7c4); }
    .tab-empty-state i { font-size: 2.4rem; margin-bottom: 12px; color: rgba(153,174,214,.35); display: block; }
    .tab-empty-state a { color: var(--bcp-cyan-400, #62c9f5); text-decoration: underline; font-weight: 650; }

    /* Module mini cards — theme-aware */
    .module-mini-card {
        padding: 12px 16px; border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
        border-radius: 10px; margin-bottom: 10px;
        display: flex; align-items: center; gap: 14px;
        background: var(--bcp-card, transparent);
        transition: border-color .16s, background .16s;
    }
    .module-mini-card:hover { border-color: rgba(98,201,245,.4); background: rgba(77,143,240,.05); }
    .module-mini-icon {
        width: 38px; height: 38px; flex: none; border-radius: 10px;
        background: rgba(77,143,240,.13); color: var(--bcp-cyan-400, #62c9f5);
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .module-mini-body { flex: 1; min-width: 0; }
    .module-mini-title { font-weight: 650; font-size: .86rem; color: var(--bcp-ink, #eef4ff); }
    .module-mini-meta { font-size: .74rem; color: var(--bcp-muted, #98a7c4); margin-top: 3px; }
    .badge-published {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700;
        background: rgba(52,211,153,.14); color: #6ee7b7;
    }
    .badge-draft {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700;
        background: rgba(251,191,36,.14); color: #fcd34d;
    }

    @media (max-width: 760px) {
        .course-hero { flex-direction: column; align-items: flex-start; padding: 20px 22px; }
        .ch-stats { width: 100%; grid-template-columns: repeat(4, 1fr); }
        .course-tab { padding: 10px 13px; font-size: .78rem; }
    }
    </style>

    
    <section class="course-hero">
        <div class="ch-main">
            <span class="ch-kicker"><i class="fa-solid fa-book-open"></i> Course overview</span>
            <h1><?php echo e($course->name ?? $course->title); ?></h1>
            <p><?php echo e($course->description ? Str::limit($course->description, 180) : 'No description provided for this course yet.'); ?></p>
            <div class="ch-meta">
                <span class="ch-chip"><i class="fa-solid fa-hashtag"></i> <?php echo e($course->code); ?></span>
                <?php if($course->credits): ?>
                    <span class="ch-chip"><i class="fa-solid fa-coins"></i> <?php echo e($course->credits); ?> Credits</span>
                <?php endif; ?>
                <?php if($course->duration_weeks): ?>
                    <span class="ch-chip"><i class="fa-solid fa-calendar-week"></i> <?php echo e($course->duration_weeks); ?> weeks</span>
                <?php endif; ?>
                <span class="ch-chip">
                    <?php if(($course->status ?? 'draft') === 'published'): ?>
                        <i class="fa-solid fa-circle-check" style="color:#34d399"></i> Published
                    <?php elseif(($course->status ?? 'draft') === 'archived'): ?>
                        <i class="fa-solid fa-box-archive" style="color:#cbd5e1"></i> Archived
                    <?php else: ?>
                        <i class="fa-solid fa-pen-ruler" style="color:#fbbf24"></i> Draft
                    <?php endif; ?>
                </span>
                <?php if($course->academicPeriod): ?>
                    <span class="ch-chip"><i class="fa-solid fa-calendar"></i> <?php echo e($course->academicPeriod->name); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ch-stats">
            <div class="ch-stat"><span class="ch-stat-value"><?php echo e($modules->count()); ?></span><span class="ch-stat-label">Modules</span></div>
            <div class="ch-stat"><span class="ch-stat-value"><?php echo e($lessons->count()); ?></span><span class="ch-stat-label">Lessons</span></div>
            <div class="ch-stat"><span class="ch-stat-value"><?php echo e($assignments->count()); ?></span><span class="ch-stat-label">Assignments</span></div>
            <div class="ch-stat"><span class="ch-stat-value"><?php echo e($quizzes->count()); ?></span><span class="ch-stat-label">Quizzes</span></div>
        </div>
    </section>

    
    <nav class="course-tabs" role="tablist" aria-label="Course sections">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('instructor.courses.show', ['course' => $course, 'tab' => $tab])); ?>"
               class="course-tab <?php echo e($activeTab === $tab ? 'active' : ''); ?>"
               role="tab" aria-selected="<?php echo e($activeTab === $tab ? 'true' : 'false'); ?>">
                <i class="fa-solid <?php echo e($tabIcons[$tab]); ?>"></i>
                <span><?php echo e(ucfirst($tab)); ?></span>
                <span class="tab-badge"><?php echo e($tabCounts[$tab]); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    
    <div class="tab-content-area">
        <?php if($activeTab === 'modules'): ?>
            <?php echo $__env->make('instructor.courses.modules._tab_content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'lessons'): ?>
            <?php echo $__env->make('instructor.courses._tab_lessons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'assignments'): ?>
            <?php echo $__env->make('instructor.courses._tab_assignments', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'quizzes'): ?>
            <?php echo $__env->make('instructor.courses._tab_quizzes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'discussions'): ?>
            <?php echo $__env->make('instructor.courses._tab_discussions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($activeTab === 'announcements'): ?>
            <?php echo $__env->make('instructor.courses._tab_announcements', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\show.blade.php ENDPATH**/ ?>