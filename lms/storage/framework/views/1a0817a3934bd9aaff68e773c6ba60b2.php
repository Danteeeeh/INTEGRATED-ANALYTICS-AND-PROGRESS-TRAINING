<?php $__env->startSection('title', 'Instructor Dashboard'); ?>
<?php
    $activeNav = 'dashboard';
    $pageTitle = 'Instructor Dashboard';
    $pageIcon = '<i class="fa-solid fa-chalkboard-user"></i>';
    $stats ??= [];
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-chalkboard-user"></i>
            Instructor Dashboard
        </h2>
    </div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>

<style>
    /* Scoped theme tokens — dark (default) + light-mode overrides. */
    .main:has(.dash-scope) .content{
        --id-bg:#0d1220;--id-surface:#151c2e;--id-surface-2:#1a2236;--id-surface-3:#202a42;
        --id-line:rgba(140,163,210,.14);--id-line-strong:rgba(140,163,210,.22);
        --id-text:#eef3ff;--id-muted:#9aa9c7;--id-faint:#72809e;
        --id-brand:#5b8cff;--id-brand-2:#3a6af0;--id-cyan:#5cc9f5;
        --id-green:#34d399;--id-amber:#fbbf24;--id-rose:#fb7185;--id-violet:#a78bfa;
        --id-shadow:0 10px 30px rgba(2,6,18,.35);
        --id-shadow-lg:0 18px 50px rgba(2,6,18,.45);
    }
    body.light-mode .main:has(.dash-scope) .content{
        --id-bg:#eef2f9;--id-surface:#ffffff;--id-surface-2:#f7f9fd;--id-surface-3:#eef2f9;
        --id-line:rgba(24,44,84,.10);--id-line-strong:rgba(24,44,84,.16);
        --id-text:#16233c;--id-muted:#5a6b8a;--id-faint:#8a97b1;
        --id-shadow:0 8px 24px rgba(24,44,84,.08);
        --id-shadow-lg:0 16px 42px rgba(24,44,84,.14);
    }
    .main:has(.dash-scope) .content{
        background:radial-gradient(1000px 420px at 85% -8%,rgba(91,140,255,.10),transparent 55%),
                   radial-gradient(700px 380px at -10% 12%,rgba(92,201,245,.07),transparent 50%),
                   var(--id-bg);
    }
</style>
<div class="dash-scope">
    <div class="user-page">
    
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Welcome back, '.e(auth()->user()->name ?? 'Instructor').'','subtitle' => 'Keep your classes moving forward — here\'s what needs your attention.','icon' => 'fa-chalkboard-user','kicker' => 'Teaching workspace']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Welcome back, '.e(auth()->user()->name ?? 'Instructor').'','subtitle' => 'Keep your classes moving forward — here\'s what needs your attention.','icon' => 'fa-chalkboard-user','kicker' => 'Teaching workspace']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="live-dot"></span>
            <span><?php echo e(now()->format('l, F j, Y')); ?></span>
            <span>·</span>
            <span><?php echo e(($stats['published_courses'] ?? 0)); ?> published · <?php echo e(($stats['draft_courses'] ?? 0)); ?> draft</span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.courses.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-book-open"></i> My courses</a>
            <a href="<?php echo e(route('instructor.classes.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-school"></i> My classes</a>
            <a href="<?php echo e(route('instructor.enrollments.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-user-plus"></i> Enrollments</a>
            <div class="dash-action dropdown">
                <button class="dash-action-btn"><i class="fa-solid fa-download"></i> Export</button>
                <div class="dropdown-content">
                    <a href="<?php echo e(route('instructor.dashboard.export', ['type' => 'students', 'format' => 'csv'])); ?>" class="dropdown-item"><i class="fa-solid fa-users"></i> Students Data</a>
                    <a href="<?php echo e(route('instructor.dashboard.export', ['type' => 'grades', 'format' => 'csv'])); ?>" class="dropdown-item"><i class="fa-solid fa-chart-line"></i> Grades Data</a>
                    <a href="<?php echo e(route('instructor.dashboard.export', ['type' => 'performance', 'format' => 'csv'])); ?>" class="dropdown-item"><i class="fa-solid fa-poll"></i> Performance Data</a>
                </div>
            </div>
            <button onclick="refreshDashboard()" class="btn btn-secondary"><i class="fa-solid fa-sync-alt"></i> Refresh</button>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $attributes = $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $component = $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>

    
    <section class="dash-search">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" id="globalSearch" placeholder="Search students, courses, classes, assignments, quizzes..." class="search-input">
                <select id="searchType" class="search-type">
                    <option value="all">All</option>
                    <option value="students">Students</option>
                    <option value="courses">Courses</option>
                    <option value="classes">Classes</option>
                    <option value="assignments">Assignments</option>
                    <option value="quizzes">Quizzes</option>
                </select>
            </div>
            <div id="searchResults" class="search-results hidden"></div>
        </div>
    </section>

    
    <div class="user-stat-grid">
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'My Courses','value' => ''.e($stats['my_courses'] ?? 0).'','icon' => 'fa-book','valueId' => 'stat-courses-value','trend' => ''.e($stats['draft_courses'] ?? 0).' in draft','footer' => 'Courses you teach']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'My Courses','value' => ''.e($stats['my_courses'] ?? 0).'','icon' => 'fa-book','valueId' => 'stat-courses-value','trend' => ''.e($stats['draft_courses'] ?? 0).' in draft','footer' => 'Courses you teach']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'My Classes','value' => ''.e($stats['my_classes'] ?? 0).'','icon' => 'fa-school','valueId' => 'stat-classes-value','trend' => 'Live']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'My Classes','value' => ''.e($stats['my_classes'] ?? 0).'','icon' => 'fa-school','valueId' => 'stat-classes-value','trend' => 'Live']); ?>
             <?php $__env->slot('footer', null, []); ?> <span id="stat-students-value"><?php echo e($stats['total_students'] ?? 0); ?></span> total students <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php $compRate = (float)($stats['completion_rate'] ?? 0); ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Completion Rate','value' => ''.e(round($compRate)).'%','icon' => 'fa-chart-line','valueId' => 'stat-completion-value','trend' => 'Avg','footer' => ''.e($stats['completed_enrollments'] ?? 0).' of '.e($stats['active_enrollments'] ?? 0).' completed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Completion Rate','value' => ''.e(round($compRate)).'%','icon' => 'fa-chart-line','valueId' => 'stat-completion-value','trend' => 'Avg','footer' => ''.e($stats['completed_enrollments'] ?? 0).' of '.e($stats['active_enrollments'] ?? 0).' completed']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Pending Submissions','value' => ''.e($stats['pending_submissions'] ?? 0).'','icon' => 'fa-clock','valueId' => 'stat-pending-value','trend' => 'Action needed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Pending Submissions','value' => ''.e($stats['pending_submissions'] ?? 0).'','icon' => 'fa-clock','valueId' => 'stat-pending-value','trend' => 'Action needed']); ?>
             <?php $__env->slot('footer', null, []); ?> <span id="stat-atrisk-value"><?php echo e($stats['at_risk_count'] ?? 0); ?></span> at-risk students <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-chart-simple"></i> Class performance</h4>
                <span class="panel-count"><?php echo e(count($stats['class_performance'] ?? [])); ?></span>
            </div>
            <ul class="perf-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['class_performance'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $pClass = $perf['class'] ?? null; ?>
                    <?php if($pClass): ?>
                        <li class="perf-item">
                            <div class="perf-top">
                                <p class="perf-name"><?php echo e($pClass->code ?? ''); ?> — <?php echo e($pClass->course?->title ?? 'Class'); ?></p>
                                <span class="perf-score"><?php echo e(number_format($perf['average_grade'] ?? 0, 1)); ?>%</span>
                            </div>
                            <div class="perf-bar"><span style="width: <?php echo e(min(($perf['average_grade'] ?? 0), 100)); ?>%"></span></div>
                            <div class="perf-meta">
                                <span><?php echo e($pClass->enrollments->where('status', 'active')->count()); ?> students</span>
                                <span><?php echo e(round($perf['completion_rate'] ?? 0)); ?>% completion</span>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-school"></i> No classes yet</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-triangle-exclamation"></i> Needs attention</h4>
                <span class="panel-count"><?php echo e($stats['at_risk_count'] ?? 0); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['at_risk_students'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-rose"><i class="fa-solid fa-user"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e($enrollment->student?->name); ?></p>
                            <p class="dash-list-sub"><?php echo e($enrollment->class?->course?->title ?? ''); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip m-rose"><?php echo e($enrollment->final_grade ? number_format($enrollment->final_grade, 1) . '%' : 'No grade'); ?></span>
                            <span class="dash-list-date"><?php echo e($enrollment->class?->code); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-face-smile"></i> No at-risk students — great job!</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-bell"></i> Upcoming</h4>
                <span class="panel-count" id="upcoming-count"><?php echo e(($stats['upcoming_assignments'] ?? collect())->count() + ($stats['upcoming_quizzes'] ?? collect())->count()); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['upcoming_assignments'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon"><i class="fa-solid fa-tasks"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e($assignment->title); ?></p>
                            <p class="dash-list-sub">Assignment · <?php echo e($assignment->class?->code ?? ''); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip m-amber">Due <?php echo e($assignment->due_date?->format('M j')); ?></span>
                            <span class="dash-list-date"><?php echo e($assignment->due_date?->diffForHumans()); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
                <?php $__empty_1 = true; $__currentLoopData = ($stats['upcoming_quizzes'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-violet"><i class="fa-solid fa-question-circle"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e($quiz->title); ?></p>
                            <p class="dash-list-sub">Quiz · <?php echo e($quiz->class?->code ?? ''); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip m-blue">Opens <?php echo e($quiz->availability_from?->format('M j')); ?></span>
                            <span class="dash-list-date"><?php echo e($quiz->availability_from?->diffForHumans()); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
                <?php if(($stats['upcoming_assignments'] ?? [])->isEmpty() && ($stats['upcoming_quizzes'] ?? [])->isEmpty()): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-calendar-check"></i> Nothing scheduled</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-user-plus"></i> Recent enrollments</h4>
                <span class="panel-count"><?php echo e(($stats['recent_enrollments'] ?? collect())->count()); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_enrollments'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-green"><i class="fa-solid fa-user-graduate"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e($enrollment->student?->name); ?></p>
                            <p class="dash-list-sub"><?php echo e($enrollment->class?->course?->title ?? $enrollment->class?->code); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip <?php echo e($enrollment->status === 'active' ? 'm-green' : 'm-gray'); ?>"><?php echo e(ucfirst($enrollment->status)); ?></span>
                            <span class="dash-list-date"><?php echo e($enrollment->created_at?->diffForHumans()); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-user-plus"></i> No recent enrollments</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-comments"></i> Recent discussions</h4>
                <span class="panel-count"><?php echo e($stats['total_discussions'] ?? 0); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_discussion_posts'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-violet"><i class="fa-solid fa-comment"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e($post->discussion?->title); ?></p>
                            <p class="dash-list-sub"><?php echo e($post->author?->name); ?> · <?php echo e(Str::limit($post->body ?? '', 60)); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-list-date"><?php echo e($post->created_at?->diffForHumans()); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-comments"></i> No discussions yet</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-bullhorn"></i> Announcements</h4>
                <span class="panel-count"><?php echo e($stats['total_announcements'] ?? 0); ?></span>
            </div>
            <ul class="announce-feed">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_announcements'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="announce-item">
                        <div class="announce-date">
                            <span class="ad-day"><?php echo e($announcement->created_at?->format('j')); ?></span>
                            <span class="ad-mon"><?php echo e($announcement->created_at?->format('M')); ?></span>
                        </div>
                        <div class="announce-body">
                            <p class="announce-title"><?php echo e($announcement->title); ?></p>
                            <p><?php echo e(Str::limit($announcement->body ?? $announcement->content ?? '', 90)); ?></p>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-bullhorn"></i> No announcements yet</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <section class="dash-analytics">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-chart-pie"></i> Performance Analytics</h4>
            <div class="analytics-controls">
                <select id="analyticsPeriod" class="analytics-select">
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="semester">This Semester</option>
                    <option value="year">This Year</option>
                </select>
                <select id="analyticsType" class="analytics-select">
                    <option value="enrollment">Enrollment</option>
                    <option value="performance" selected>Performance</option>
                    <option value="attendance">Attendance</option>
                    <option value="engagement">Engagement</option>
                </select>
                <button onclick="loadAnalytics()" class="analytics-btn"><i class="fa-solid fa-sync-alt"></i> Load</button>
            </div>
        </div>
        <div class="analytics-content">
            <div id="analytics-toast" class="analytics-toast"></div>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-chart-line"></i> Grade Distribution</h5>
                    <div id="gradeDistributionChart" class="chart-container">
                        <div class="chart-placeholder">
                            <i class="fa-solid fa-chart-bar"></i>
                            <p>Loading analytics…</p>
                        </div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-users"></i> Enrollment Trends</h5>
                    <div id="enrollmentTrendsChart" class="chart-container">
                        <div class="chart-placeholder">
                            <i class="fa-solid fa-chart-area"></i>
                            <p>Loading analytics…</p>
                        </div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-clipboard-check"></i> Attendance Overview</h5>
                    <div id="attendanceOverviewChart" class="chart-container">
                        <div class="chart-placeholder">
                            <i class="fa-solid fa-chart-pie"></i>
                            <p>Loading analytics…</p>
                        </div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-comments"></i> Engagement Metrics</h5>
                    <div id="engagementMetricsChart" class="chart-container">
                        <div class="chart-placeholder">
                            <i class="fa-solid fa-chart-line"></i>
                            <p>Loading analytics…</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Dropdown functionality
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const button = dropdown.querySelector('.dash-action-btn');
            const content = dropdown.querySelector('.dropdown-content');

            button.addEventListener('click', (e) => {
                e.stopPropagation();
                content.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                content.classList.remove('show');
            });
        });

        // Real-time dashboard refresh
        async function refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            try {
                // Clear cache and reload
                await fetch('<?php echo e(route('instructor.dashboard.clear-cache')); ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Content-Type': 'application/json',
                    },
                });

                // Reload the page
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                refreshBtn.innerHTML = originalContent;
                refreshBtn.disabled = false;
                alert('Error refreshing dashboard. Please try again.');
            }
        }

        // Auto-refresh stats every 30 seconds — live update ng tiles nang walang page reload
        setInterval(async () => {
            try {
                const response = await fetch('<?php echo e(route('instructor.dashboard.real-time-stats')); ?>');
                if (!response.ok) throw new Error('Bad status: ' + response.status);
                const data = await response.json();

                if (data.success) {
                    const stats = data.data;
                    // Update stat tiles in-place
                    const pendingEl = document.getElementById('stat-pending-value');
                    if (pendingEl && stats.pending_submissions !== undefined) {
                        pendingEl.textContent = stats.pending_submissions;
                        const chip = document.querySelector('#stat-pending .trend-chip');
                        if (chip) {
                            const needsAction = stats.pending_submissions > 0;
                            chip.textContent = needsAction ? 'Action needed' : 'All clear';
                            chip.className = 'trend-chip ' + (needsAction ? 'new' : 'up');
                        }
                    }
                    const atRiskEl = document.getElementById('stat-atrisk-value');
                    if (atRiskEl && stats.at_risk_count !== undefined) {
                        atRiskEl.textContent = stats.at_risk_count;
                    }
                    const upcomingCountEl = document.getElementById('upcoming-count');
                    if (upcomingCountEl && stats.upcoming_assignments_count !== undefined && stats.upcoming_quizzes_count !== undefined) {
                        upcomingCountEl.textContent = stats.upcoming_assignments_count + stats.upcoming_quizzes_count;
                    }
                }
            } catch (error) {
                console.error('Error fetching real-time stats:', error);
            }
        }, 30000);

        // Search functionality
        const searchInput = document.getElementById('globalSearch');
        const searchType = document.getElementById('searchType');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            // Show loading state
            searchResults.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        async function performSearch(query) {
            const type = searchType.value;

            try {
                const response = await fetch(`<?php echo e(route('instructor.dashboard.search')); ?>?query=${encodeURIComponent(query)}&type=${type}`);
                const data = await response.json();

                if (data.success) {
                    if (data.data.length > 0) {
                        displaySearchResults(data.data, type);
                    } else {
                        searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                        searchResults.classList.remove('hidden');
                    }
                } else {
                    throw new Error(data.error || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-no-results">Error: ' + error.message + '</div>';
                searchResults.classList.remove('hidden');
            }
        }

        function searchResultTypeBadge(result, type) {
            // When searching 'all', results are merged — add a type badge
            const typeMap = {
                'students': { label: 'Student', icon: 'fa-user-graduate', cls: 'type-student' },
                'courses': { label: 'Course', icon: 'fa-book', cls: 'type-course' },
                'classes': { label: 'Class', icon: 'fa-school', cls: 'type-class' },
                'assignments': { label: 'Assignment', icon: 'fa-tasks', cls: 'type-assignment' },
                'quizzes': { label: 'Quiz', icon: 'fa-question-circle', cls: 'type-quiz' }
            };
            if (type !== 'all') return '';
            // Infer type from fields
            let inferred = 'courses';
            if (result.email) inferred = 'students';
            else if (result.class_code && result.due_date) inferred = 'assignments';
            else if (result.class_code && result.availability_from) inferred = 'quizzes';
            else if (result.course_title && result.code) inferred = 'classes';
            const t = typeMap[inferred];
            return `<span class="type-badge ${t.cls}"><i class="fa-solid ${t.icon}"></i> ${t.label}</span>`;
        }

        function displaySearchResults(results, type) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                searchResults.classList.remove('hidden');
                return;
            }

            let html = '<div class="search-results-list">';

            results.forEach(result => {
                // Determine navigation target per result type
                let href = '#';
                if (type === 'students' || result.email) {
                    href = result.id ? `<?php echo e(url('instructor/enrollments')); ?>` : '#';
                } else if (type === 'courses' || (result.title && !result.due_date && !result.availability_from && !result.class_code)) {
                    href = result.id ? `<?php echo e(url('instructor/courses')); ?>/${result.id}` : '#';
                } else if (type === 'classes' || (result.code && result.course_title)) {
                    href = result.id ? `<?php echo e(url('instructor/classes')); ?>/${result.id}` : '#';
                } else if (type === 'assignments' || type === 'quizzes') {
                    href = result.id ? `<?php echo e(url('instructor/classes')); ?>` : '#';
                }

                html += `
                    <a href="${href}" class="search-result-item">
                        <div class="search-result-title">${result.name || result.title || result.code}
                            ${searchResultTypeBadge(result, type)}
                        </div>
                        <div class="search-result-details">
                            ${result.email ? `<span>${result.email}</span>` : ''}
                            ${result.course_title ? `<span>${result.course_title}</span>` : ''}
                            ${result.class_code ? `<span>${result.class_code}</span>` : ''}
                            ${result.status ? `<span class="status-badge ${result.status}">${result.status}</span>` : ''}
                            ${result.due_date ? `<span>Due ${result.due_date}</span>` : ''}
                            ${result.availability_from ? `<span>Opens ${result.availability_from}</span>` : ''}
                        </div>
                    </a>
                `;
            });

            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');
        }

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                searchResults.classList.add('hidden');
            }
        });

        // Analytics functionality
        let chartInstances = {};

        function destroyChart(id) {
            if (chartInstances[id]) {
                chartInstances[id].destroy();
                delete chartInstances[id];
            }
        }

        async function loadAnalytics() {
            const period = document.getElementById('analyticsPeriod').value;
            const type = document.getElementById('analyticsType').value;
            const button = document.querySelector('.analytics-btn');

            // Show loading state
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            button.disabled = true;

            try {
                const response = await fetch(`<?php echo e(route('instructor.dashboard.analytics')); ?>?period=${period}&type=${type}`);
                const data = await response.json();

                if (data.success) {
                    displayAnalytics(data.data, type);
                    const toast = document.getElementById('analytics-toast');
                    if (toast) {
                        toast.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} analytics loaded for ${period === 'week' ? 'this week' : period === 'month' ? 'this month' : period === 'semester' ? 'this semester' : 'this year'}`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                } else {
                    throw new Error(data.error || 'Failed to load analytics');
                }
            } catch (error) {
                console.error('Analytics error:', error);
                if (typeof LMS !== 'undefined' && LMS.toast) {
                    LMS.toast('Error loading analytics: ' + error.message, 'error');
                } else {
                    alert('Error loading analytics: ' + error.message);
                }
            } finally {
                // Reset button state
                button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load';
                button.disabled = false;
            }
        }

        function displayAnalytics(analytics, type) {
            switch (type) {
                case 'enrollment':
                    displayEnrollmentAnalytics(analytics);
                    break;
                case 'performance':
                    displayPerformanceAnalytics(analytics);
                    break;
                case 'attendance':
                    displayAttendanceAnalytics(analytics);
                    break;
                case 'engagement':
                    displayEngagementAnalytics(analytics);
                    break;
            }
        }

        function displayEnrollmentAnalytics(analytics) {
            const container = document.getElementById('enrollmentTrendsChart');
            destroyChart('enrollmentTrendsChart');

            const recent = analytics.recent_enrollments || [];

            // Cumulative enrollment counts — meaningful trend data
            const counts = [];
            recent.forEach((_, i) => counts.push(i + 1));

            container.innerHTML = '<canvas id="enrollmentChartCanvas"></canvas>';
            const ctx = document.getElementById('enrollmentChartCanvas');

            chartInstances['enrollmentTrendsChart'] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: recent.length ? recent.map(e => e.date || '') : ['No data'],
                    datasets: [{
                        label: 'Enrollments',
                        data: counts.length ? counts : [0],
                        fill: true,
                        borderColor: '#62c9f5',
                        backgroundColor: 'rgba(98, 201, 245, 0.15)',
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#62c9f5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const e = recent[context.dataIndex];
                                    return e ? `${e.student} — ${e.course}` : '';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#98a7c4' },
                            grid: { color: 'rgba(153,174,214,.12)' }
                        },
                        x: { grid: { display: false }, ticks: { color: '#98a7c4' } }
                    }
                }
            });

            // Summary overlay
            const summary = document.createElement('div');
            summary.className = 'analytics-summary';
            summary.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Total Enrollments</span>
                    <span class="summary-value">${analytics.total_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completed</span>
                    <span class="summary-value">${analytics.completed_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completion Rate</span>
                    <span class="summary-value">${analytics.completion_rate.toFixed(1)}%</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">At-Risk Students</span>
                    <span class="summary-value warning">${analytics.at_risk_students}</span>
                </div>
            `;
            container.appendChild(summary);
        }

        function displayPerformanceAnalytics(analytics) {
            const container = document.getElementById('gradeDistributionChart');
            destroyChart('gradeDistributionChart');
            const distribution = analytics.grade_distribution;

            // Doughnut chart para sa grade distribution
            container.innerHTML = '<canvas id="gradeChartCanvas"></canvas>';
            const ctx = document.getElementById('gradeChartCanvas');

            chartInstances['gradeDistributionChart'] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['A (90-100)', 'B (80-89)', 'C (70-79)', 'D (60-69)', 'F (0-59)'],
                    datasets: [{
                        data: [distribution.A, distribution.B, distribution.C, distribution.D, distribution.F],
                        backgroundColor: ['#34d399', '#62c9f5', '#fbbf24', '#fb923c', '#fb7185'],
                        borderColor: '#151c2c',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#c7d4ec', boxWidth: 12, padding: 12 }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const total = Object.values(distribution).reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? (context.parsed / total * 100).toFixed(1) : 0;
                                    return ` ${context.parsed} students (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Average grade line
            const avgWrap = document.createElement('div');
            avgWrap.className = 'average-grade';
            avgWrap.innerHTML = `
                <span class="avg-label"><i class="fa-solid fa-chart-simple"></i> Class Average: ${analytics.average_grade.toFixed(1)}%</span>
                <span class="avg-sub">Across ${analytics.graded_enrollments} graded enrollments</span>
            `;
            container.appendChild(avgWrap);
        }

        function displayAttendanceAnalytics(analytics) {
            const container = document.getElementById('attendanceOverviewChart');
            destroyChart('attendanceOverviewChart');

            const byClass = analytics.attendance_by_class || [];
            container.innerHTML = '<canvas id="attendanceChartCanvas"></canvas>';
            const ctx = document.getElementById('attendanceChartCanvas');

            chartInstances['attendanceOverviewChart'] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: byClass.length ? byClass.map(c => c.class_code) : ['No data'],
                    datasets: [{
                        label: 'Attendance Rate %',
                        data: byClass.length ? byClass.map(c => c.attendance_rate.toFixed(1)) : [0],
                        backgroundColor: 'rgba(52, 211, 153, 0.6)',
                        borderColor: '#34d399',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const cls = byClass[context.dataIndex];
                                    return cls ? ` ${cls.attendance_rate.toFixed(1)}% (${cls.total_records} records)` : '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(153,174,214,.12)' },
                            ticks: { callback: (v) => v + '%', color: '#98a7c4' }
                        },
                        y: { grid: { display: false }, ticks: { color: '#c7d4ec' } }
                    }
                }
            });

            // Average attendance summary
            const summary = document.createElement('div');
            summary.className = 'attendance-summary';
            summary.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Average Attendance Rate</span>
                    <span class="summary-value">${analytics.average_attendance_rate.toFixed(1)}%</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Classes Tracked</span>
                    <span class="summary-value">${analytics.total_classes}</span>
                </div>
            `;
            container.appendChild(summary);
        }

        function displayEngagementAnalytics(analytics) {
            const container = document.getElementById('engagementMetricsChart');
            container.innerHTML = `
                <div class="engagement-metrics">
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-comments"></i></span>
                        <span class="metric-label">Discussions</span>
                        <span class="metric-value">${analytics.total_discussions}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-comment-dots"></i></span>
                        <span class="metric-label">Recent Posts</span>
                        <span class="metric-value">${analytics.recent_discussion_posts}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-bullhorn"></i></span>
                        <span class="metric-label">Announcements</span>
                        <span class="metric-value">${analytics.total_announcements}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-video"></i></span>
                        <span class="metric-label">Virtual Classes</span>
                        <span class="metric-value">${analytics.virtual_classes_held}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-users"></i></span>
                        <span class="metric-label">Participation Rate</span>
                            <span class="metric-value">${analytics.student_participation_rate.toFixed(1)}%</span>
                    </div>
                </div>
            `;
        }

        // Auto-load performance analytics on page load (silent — no toast on first load)
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.analytics-btn');
            if (button) {
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
                button.disabled = true;
            }
            const period = document.getElementById('analyticsPeriod').value;
            const type = document.getElementById('analyticsType').value;
            fetch(`<?php echo e(route('instructor.dashboard.analytics')); ?>?period=${period}&type=${type}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayAnalytics(data.data, type);
                    } else {
                        console.error('Failed to load initial analytics:', data.error);
                    }
                })
                .catch(err => console.error('Initial analytics error:', err))
                .finally(() => {
                    if (button) {
                        button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load';
                        button.disabled = false;
                    }
                });
        });
    </script>

    
    <style>
        /* ── Hero ─────────────────────────────────────────── */
        .dash-scope .dash-hero{
            position:relative;overflow:hidden;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;
            padding:30px 32px;margin-bottom:22px;border-radius:20px;
            border:1px solid rgba(120,160,255,.22);
            background:
                radial-gradient(700px 260px at 88% -20%,rgba(92,201,245,.20),transparent 60%),
                radial-gradient(500px 300px at 8% 120%,rgba(167,139,250,.14),transparent 60%),
                linear-gradient(135deg,#16318f 0%,#1a3fb5 45%,#0e1530 100%);
            box-shadow:var(--id-shadow-lg);
        }
        .dash-scope .dash-hero::before{
            content:"";position:absolute;inset:0;pointer-events:none;opacity:.5;
            background-image:radial-gradient(rgba(255,255,255,.14) 1px,transparent 1px);
            background-size:22px 22px;
            mask-image:linear-gradient(115deg,transparent 30%,#000 75%);
        }
        .dash-scope .dash-hero::after{
            content:"";position:absolute;right:-90px;top:-90px;width:280px;height:280px;border-radius:50%;
            border:1px solid rgba(255,255,255,.14);pointer-events:none;
        }
        .dash-scope .dash-hero>div{position:relative;z-index:1}
        .dash-scope .dash-hero-kicker{
            display:inline-flex;align-items:center;gap:7px;margin-bottom:12px;padding:5px 11px;border-radius:999px;
            background:rgba(92,201,245,.14);border:1px solid rgba(92,201,245,.3);
            color:#9be0ff;font-size:.66rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;
        }
        .dash-scope .dash-hero h1{margin:0;color:#fff;font-size:clamp(1.55rem,2.6vw,2.15rem);letter-spacing:-.035em;line-height:1.1}
        .dash-scope .dash-hero p{margin:9px 0 0;max-width:560px;color:#c9d8f6;font-size:.9rem;line-height:1.6}
        .dash-scope .dash-hero-meta{display:flex;align-items:center;gap:10px;margin-top:16px;color:#a9bce4;font-size:.76rem;font-weight:600}
        .dash-scope .dash-hero-sep{width:3px;height:3px;border-radius:50%;background:#a9bce4;opacity:.6}
        .dash-scope .live-dot{width:8px;height:8px;border-radius:50%;background:#34d399;box-shadow:0 0 0 3px rgba(52,211,153,.18);animation:idPulse 2.2s ease infinite}
        @keyframes idPulse{0%,100%{box-shadow:0 0 0 3px rgba(52,211,153,.18)}50%{box-shadow:0 0 0 6px rgba(52,211,153,.05)}}
        .dash-scope .dash-hero-actions{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}
        .dash-scope .dash-action{
            display:inline-flex;align-items:center;gap:8px;padding:10px 15px;border-radius:11px;
            border:1px solid rgba(255,255,255,.2);color:#fff;background:rgba(255,255,255,.08);
            font-size:.78rem;font-weight:700;text-decoration:none;white-space:nowrap;backdrop-filter:blur(8px);
            transition:background .18s,transform .18s,box-shadow .18s,border-color .18s;
        }
        .dash-scope .dash-action:hover{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.34);transform:translateY(-2px);box-shadow:0 12px 26px rgba(0,0,0,.3)}

        /* ── Search ───────────────────────────────────────── */
        .dash-scope .dash-search{margin:0 0 22px}
        .dash-scope .search-container{position:relative;max-width:680px;margin:0 auto}
        .dash-scope .search-input-wrapper{
            display:flex;align-items:center;padding:13px 16px;border-radius:14px;
            background:var(--id-surface);border:1px solid var(--id-line-strong);
            box-shadow:var(--id-shadow);transition:border-color .2s,box-shadow .2s;
        }
        .dash-scope .search-input-wrapper:focus-within{border-color:rgba(91,140,255,.55);box-shadow:0 0 0 4px rgba(91,140,255,.14)}
        .dash-scope .search-icon{color:var(--id-faint);margin-right:12px}
        .dash-scope .search-input{flex:1;border:none;outline:none;font-size:15px;padding:6px 0;background:transparent;color:var(--id-text)}
        .dash-scope .search-input::placeholder{color:var(--id-faint)}
        .dash-scope .search-type{
            border:1px solid var(--id-line-strong);background:var(--id-surface-3);color:var(--id-text);
            padding:7px 11px;border-radius:9px;margin-left:12px;cursor:pointer;font-size:13px;
        }
        .dash-scope .search-results{
            position:absolute;top:100%;left:0;right:0;margin-top:8px;z-index:100;max-height:400px;overflow-y:auto;
            background:var(--id-surface-2);border:1px solid var(--id-line-strong);border-radius:14px;box-shadow:var(--id-shadow-lg);
        }
        .dash-scope .search-results.hidden{display:none}
        .dash-scope .search-results-list{padding:6px 0}
        .dash-scope .search-result-item{display:block;padding:12px 16px;border-bottom:1px solid var(--id-line);text-decoration:none;color:inherit;transition:background .18s}
        .dash-scope .search-result-item:hover{background:rgba(91,140,255,.09)}
        .dash-scope .search-result-title{font-weight:650;color:var(--id-text);margin-bottom:4px}
        .dash-scope .search-result-details{font-size:13px;color:var(--id-muted);display:flex;gap:12px;flex-wrap:wrap}
        .dash-scope .search-no-results,.dash-scope .search-loading{padding:20px;text-align:center;color:var(--id-muted)}
        .dash-scope .search-loading i{margin-right:8px}
        .dash-scope .type-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:750;letter-spacing:.03em;margin-left:8px;vertical-align:middle}
        .dash-scope .type-badge.type-student{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .type-badge.type-course{background:rgba(91,140,255,.15);color:#9db9ff}
        .dash-scope .type-badge.type-class{background:rgba(92,201,245,.13);color:#7dd6f8}
        .dash-scope .type-badge.type-assignment{background:rgba(251,191,36,.13);color:#fcd34d}
        .dash-scope .type-badge.type-quiz{background:rgba(167,139,250,.15);color:#c4b5fd}
        .dash-scope .status-badge{padding:2px 8px;border-radius:12px;font-size:12px;font-weight:500}
        .dash-scope .status-badge.published{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .status-badge.draft{background:rgba(251,191,36,.13);color:#fcd34d}
        .dash-scope .status-badge.active{background:rgba(91,140,255,.15);color:#9db9ff}

        /* ── Stat tiles ───────────────────────────────────── */
        .dash-scope .dash-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px}
        .dash-scope .dash-stat{
            position:relative;overflow:hidden;display:flex;flex-direction:column;gap:11px;padding:20px 20px 18px;
            background:var(--id-surface);border:1px solid var(--id-line);border-radius:16px;box-shadow:var(--id-shadow);
            transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;
        }
        .dash-scope .dash-stat::before{content:"";position:absolute;inset:0 0 auto 0;height:3px;background:var(--id-accent,linear-gradient(90deg,var(--id-brand),var(--id-cyan)));opacity:.9}
        .dash-scope .dash-stat::after{display:none}
        .dash-scope .dash-stat:hover{transform:translateY(-3px);border-color:var(--id-line-strong);box-shadow:var(--id-shadow-lg)}
        .dash-scope #stat-courses{--id-accent:linear-gradient(90deg,#3b82f6,#60a5fa)}
        .dash-scope #stat-classes{--id-accent:linear-gradient(90deg,#06b6d4,#5cc9f5)}
        .dash-scope #stat-completion{--id-accent:linear-gradient(90deg,#8b5cf6,#a78bfa)}
        .dash-scope #stat-pending{--id-accent:linear-gradient(90deg,#fb7185,#fda4af)}
        .dash-scope .dash-stat-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:12px;color:#fff;font-size:.95rem}
        .dash-scope .dash-stat-icon.c-blue{background:linear-gradient(135deg,#3b82f6,#1d4ed8);box-shadow:0 8px 18px rgba(59,130,246,.32)}
        .dash-scope .dash-stat-icon.c-cyan{background:linear-gradient(135deg,#22d3ee,#0891b2);box-shadow:0 8px 18px rgba(34,211,238,.3)}
        .dash-scope .dash-stat-icon.c-violet{background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 8px 18px rgba(139,92,246,.32)}
        .dash-scope .dash-stat-icon.c-rose{background:linear-gradient(135deg,#fb7185,#e11d48);box-shadow:0 8px 18px rgba(251,113,133,.3)}
        .dash-scope .dash-stat-label{color:var(--id-muted);font-size:.68rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
        .dash-scope .dash-stat-value{font-size:1.85rem;font-weight:800;color:var(--id-text);letter-spacing:-.025em;line-height:1.05}
        .dash-scope .dash-stat-value small{font-size:.9rem;font-weight:700;color:var(--id-muted)}
        .dash-scope .dash-stat-foot{display:flex;align-items:center;gap:7px;color:var(--id-muted);font-size:.73rem;margin-top:-2px}
        .dash-scope .trend-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:.63rem;font-weight:800;letter-spacing:.04em}
        .dash-scope .trend-chip.up{color:#6ee7b7;background:rgba(52,211,153,.13);border:1px solid rgba(52,211,153,.28)}
        .dash-scope .trend-chip.flat{color:#9db9ff;background:rgba(91,140,255,.13);border:1px solid rgba(91,140,255,.28)}
        .dash-scope .trend-chip.new{color:#fda4af;background:rgba(251,113,133,.13);border:1px solid rgba(251,113,133,.3)}
        body.light-mode .dash-scope .trend-chip.up{color:#047857}
        body.light-mode .dash-scope .trend-chip.flat{color:#1d4ed8}
        body.light-mode .dash-scope .trend-chip.new{color:#be123c}

        /* ── Panels & lists ───────────────────────────────── */
        .dash-scope .dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;margin-bottom:18px}
        .dash-scope .dash-panel{overflow:hidden;background:var(--id-surface);border:1px solid var(--id-line);border-radius:16px;box-shadow:var(--id-shadow)}
        .dash-scope .dash-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:15px 20px;border-bottom:1px solid var(--id-line)}
        .dash-scope .dash-panel-head h4{margin:0;color:var(--id-text);font-size:.78rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;display:flex;align-items:center;gap:9px}
        .dash-scope .dash-panel-head h4 i{color:var(--id-brand)}
        .dash-scope .dash-panel-head .panel-count{min-width:26px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:rgba(91,140,255,.14);color:var(--id-brand);font-size:.66rem;font-weight:800;padding:0 8px}
        body.light-mode .dash-scope .dash-panel-head .panel-count{color:#1d4ed8}

        .dash-scope .dash-list-item{display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .dash-list-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .dash-list-item+.dash-list-item{border-top:1px solid var(--id-line)}
        .dash-scope .dash-list-icon{display:grid;place-items:center;width:37px;height:37px;flex:none;border-radius:11px;font-size:.85rem;background:rgba(91,140,255,.13);color:var(--id-brand)}
        .dash-scope .dash-list-icon.i-violet{background:rgba(167,139,250,.14);color:#c4b5fd}
        .dash-scope .dash-list-icon.i-green{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .dash-list-icon.i-rose{background:rgba(251,113,133,.13);color:#fda4af}
        .dash-scope .dash-list-icon.i-amber{background:rgba(251,191,36,.13);color:#fcd34d}
        body.light-mode .dash-scope .dash-list-icon{color:#1d4ed8}
        body.light-mode .dash-scope .dash-list-icon.i-violet{color:#7c3aed}
        body.light-mode .dash-scope .dash-list-icon.i-green{color:#059669}
        body.light-mode .dash-scope .dash-list-icon.i-rose{color:#e11d48}
        body.light-mode .dash-scope .dash-list-icon.i-amber{color:#b45309}
        .dash-scope .dash-meta-chip{padding:3px 9px;border-radius:999px;font-size:.64rem;font-weight:800;letter-spacing:.03em;white-space:nowrap}
        .dash-scope .dash-meta-chip.m-green{color:#6ee7b7;background:rgba(52,211,153,.13)}
        .dash-scope .dash-meta-chip.m-amber{color:#fcd34d;background:rgba(251,191,36,.13)}
        .dash-scope .dash-meta-chip.m-rose{color:#fda4af;background:rgba(251,113,133,.13)}
        .dash-scope .dash-meta-chip.m-blue{color:#9db9ff;background:rgba(91,140,255,.14)}
        .dash-scope .dash-meta-chip.m-gray{color:var(--id-muted);background:rgba(148,163,184,.13)}
        body.light-mode .dash-scope .dash-meta-chip.m-green{color:#047857}
        body.light-mode .dash-scope .dash-meta-chip.m-amber{color:#b45309}
        body.light-mode .dash-scope .dash-meta-chip.m-rose{color:#be123c}
        body.light-mode .dash-scope .dash-meta-chip.m-blue{color:#1d4ed8}
        .dash-scope .dash-list-empty{padding:26px 18px;text-align:center;color:var(--id-muted);font-size:.82rem}
        .dash-scope .dash-list-empty i{margin-right:8px;opacity:.7}

        /* ── Class performance ────────────────────────────── */
        .dash-scope .perf-item{padding:12px 4px}
        .dash-scope .perf-item+.perf-item{border-top:1px solid var(--id-line)}
        .dash-scope .perf-name{color:var(--id-text);font-size:.83rem;font-weight:700}
        .dash-scope .perf-score{color:var(--id-brand);font-size:.74rem;font-weight:800}
        body.light-mode .dash-scope .perf-score{color:#1d4ed8}
        .dash-scope .perf-bar{height:7px;border-radius:999px;background:var(--id-surface-3);overflow:hidden;margin-top:8px}
        .dash-scope .perf-bar>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#1a3fb5,#5b8cff,#5cc9f5);transition:width .6s ease}
        .dash-scope .perf-meta{color:var(--id-muted);font-size:.66rem}

        /* ── Announcements ────────────────────────────────── */
        .dash-scope .announce-item{display:flex;gap:13px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .announce-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .announce-item+.announce-item{border-top:1px solid var(--id-line)}
        .dash-scope .announce-date{flex:none;display:flex;flex-direction:column;align-items:center;justify-content:center;width:48px;height:48px;border-radius:13px;background:linear-gradient(150deg,rgba(91,140,255,.2),rgba(92,201,245,.1));border:1px solid rgba(91,140,255,.25);color:var(--id-brand)}
        .dash-scope .announce-date .ad-day{color:var(--id-text);font-size:1.05rem;font-weight:800;line-height:1}
        .dash-scope .announce-date .ad-mon{font-size:.58rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-top:3px}
        body.light-mode .dash-scope .announce-date{color:#1d4ed8}
        .dash-scope .announce-title{color:var(--id-text)}

        /* ── Completion ring ──────────────────────────────── */
        .dash-scope .ring-bg{stroke:var(--id-surface-3)}

        /* ── Analytics ────────────────────────────────────── */
        .dash-scope .dash-analytics{margin:26px 0 8px;padding:22px;background:var(--id-surface);border:1px solid var(--id-line);border-radius:18px;box-shadow:var(--id-shadow)}
        .dash-scope .dash-analytics .dash-panel-head{padding:0 0 16px;border-bottom:1px solid var(--id-line);margin-bottom:20px}
        .dash-scope .analytics-toast{display:none;margin-bottom:16px;padding:10px 14px;border-radius:10px;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.3);color:#6ee7b7;font-size:.8rem;font-weight:650}
        .dash-scope .analytics-toast.show{display:block;animation:idFadeInOut 3s ease}
        @keyframes idFadeInOut{0%{opacity:0;transform:translateY(-4px)}10%,85%{opacity:1;transform:translateY(0)}100%{opacity:0}}
        .dash-scope .analytics-controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
        .dash-scope .analytics-select{padding:8px 12px;border:1px solid var(--id-line-strong);border-radius:9px;background:var(--id-surface-3);color:var(--id-text);cursor:pointer;font-size:13.5px}
        .dash-scope .analytics-btn{padding:9px 17px;background:linear-gradient(135deg,#3a6af0,#1a3fb5);color:#fff;border:1px solid rgba(120,160,255,.4);border-radius:9px;cursor:pointer;font-size:13.5px;font-weight:700;transition:filter .18s,transform .16s}
        .dash-scope .analytics-btn:hover:not(:disabled){filter:brightness(1.15);transform:translateY(-1px)}
        .dash-scope .analytics-btn:disabled{opacity:.7;cursor:default}
        .dash-scope .analytics-content{margin-top:20px}
        .dash-scope .analytics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
        .dash-scope .analytics-card{background:var(--id-surface-2);border:1px solid var(--id-line);border-radius:14px;padding:20px}
        .dash-scope .analytics-card h5{margin:0 0 16px;font-size:.82rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--id-text);display:flex;align-items:center;gap:8px}
        .dash-scope .analytics-card h5 i{color:var(--id-brand)}
        .dash-scope .chart-container{position:relative;height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center}
        .dash-scope .chart-container canvas{max-height:100%}
        .dash-scope .chart-placeholder{text-align:center;color:var(--id-muted)}
        .dash-scope .chart-placeholder i{font-size:44px;margin-bottom:12px;color:var(--id-line-strong)}
        .dash-scope .chart-placeholder p{margin:0;font-size:14px}
        .dash-scope .analytics-summary,.dash-scope .attendance-summary{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;width:100%;margin-top:18px;padding-top:16px;border-top:1px solid var(--id-line)}
        .dash-scope .summary-item{display:flex;flex-direction:column;gap:4px}
        .dash-scope .summary-label{font-size:11px;color:var(--id-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em}
        .dash-scope .summary-value{font-size:21px;font-weight:800;color:var(--id-text)}
        .dash-scope .summary-value.warning{color:var(--id-rose)}
        .dash-scope .average-grade{display:flex;flex-direction:column;gap:2px;margin-top:16px;padding-top:16px;border-top:1px solid var(--id-line);width:100%;text-align:center}
        .dash-scope .avg-label{font-size:14px;font-weight:700;color:var(--id-text)}
        .dash-scope .avg-sub{font-size:12px;color:var(--id-muted)}
        .dash-scope .attendance-bar{background:var(--id-surface-3)}
        .dash-scope .grade-progress{background:var(--id-surface-3)}
        .dash-scope .metric-item{display:flex;align-items:center;gap:12px;padding:13px;background:var(--id-surface-2);border:1px solid var(--id-line);border-radius:11px}
        .dash-scope .metric-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:rgba(91,140,255,.14);border-radius:12px;color:var(--id-brand);font-size:16px}
        body.light-mode .dash-scope .metric-icon{color:#1d4ed8}
        .dash-scope .metric-label{flex:1;font-size:14px;color:var(--id-muted)}
        .dash-scope .metric-value{font-size:18px;font-weight:750;color:var(--id-text)}
        .dash-scope .engagement-metrics{display:flex;flex-direction:column;gap:12px}
        .dash-scope .grade-label,.dash-scope .class-label{color:var(--id-muted)}
        .dash-scope .grade-count,.dash-scope .attendance-rate{color:var(--id-text)}

        /* ── Dropdown ─────────────────────────────────────── */
        .dash-scope .dropdown{position:relative;display:inline-block}
        .dash-scope .dash-action-btn{background:none;border:none;cursor:pointer;font-size:inherit;color:inherit}
        .dash-scope .dropdown-content{display:none;position:absolute;right:0;min-width:210px;margin-top:8px;z-index:50;overflow:hidden;background:var(--id-surface-2);border:1px solid var(--id-line-strong);border-radius:12px;box-shadow:var(--id-shadow-lg)}
        .dash-scope .dropdown-content.show{display:block}
        .dash-scope .dropdown-item{display:block;padding:12px 16px;text-decoration:none;color:var(--id-text);font-size:.84rem;transition:background .18s}
        .dash-scope .dropdown-item:hover{background:rgba(91,140,255,.1)}
        .dash-scope .dropdown-item i{margin-right:8px;color:var(--id-muted)}

        /* ── Light mode / responsive ──────────────────────── */
        body.light-mode .dash-scope .dash-stat-label{color:var(--id-muted)}
        @media (max-width:820px){
            .dash-scope .dash-hero{align-items:flex-start;flex-direction:column;padding:24px}
            .dash-scope .dash-hero-actions{justify-content:flex-start}
        }
        @media (max-width:640px){
            .dash-scope .analytics-grid{grid-template-columns:1fr}
            .dash-scope .analytics-summary,.dash-scope .attendance-summary{grid-template-columns:1fr 1fr}
            .dash-scope .dash-analytics{padding:16px}
        }
    </style>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\dashboard.blade.php ENDPATH**/ ?>