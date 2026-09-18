<?php $__env->startSection('title', $course->name ?? 'Course Details'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = $course->name ?? 'Course Details';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
    $modulesCount = $modulesCount ?? 0;
    $assignmentsCount = $assignmentsCount ?? 0;
    $quizzesCount = $quizzesCount ?? 0;
    $discussionsCount = $discussionsCount ?? 0;
    $announcementsCount = $announcementsCount ?? 0;
    $modulesProgress = max(0, min(100, (int) ($modulesProgress ?? 0)));
    $assignmentsProgress = max(0, min(100, (int) ($assignmentsProgress ?? 0)));
    $quizzesProgress = max(0, min(100, (int) ($quizzesProgress ?? 0)));
    $overallProgress = (int) round(($modulesProgress + $assignmentsProgress + $quizzesProgress) / 3);
    $courseTitle = $course->name ?? $course->title ?? 'Course Details';
    $courseDescription = trim((string) ($course->description ?? ''));
?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell course-detail-page">
    
    <section class="course-detail-hero" style="--course-progress: <?php echo e($overallProgress); ?>%" aria-labelledby="course-detail-title">
        <div class="course-detail-hero-main">
            <div class="course-detail-identity">
                <span class="course-detail-mark" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                <div>
                    <span class="course-detail-kicker">Course workspace</span>
                    <span class="course-detail-code"><?php echo e($course->code ?? 'COURSE'); ?></span>
                </div>
            </div>
            <h1 id="course-detail-title"><?php echo e($courseTitle); ?></h1>
            <p><?php echo e($courseDescription !== '' ? $courseDescription : 'Build your progress through modules, assignments, quizzes, and course discussions.'); ?></p>
            <div class="course-detail-meta" aria-label="Course summary">
                <?php if($course->credits): ?>
                    <span><i class="fa-solid fa-award" aria-hidden="true"></i> <?php echo e($course->credits); ?> credits</span>
                <?php endif; ?>
                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?php echo e($modulesCount); ?> modules</span>
                <span><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> <?php echo e($announcementsCount); ?> announcements</span>
            </div>
        </div>
        <div class="course-detail-hero-side">
            <div class="course-progress-orbit" role="img" aria-label="Overall course progress: <?php echo e($overallProgress); ?> percent">
                <div class="course-progress-orbit-inner">
                    <strong><?php echo e($overallProgress); ?>%</strong>
                    <span>overall progress</span>
                </div>
            </div>
            <a href="<?php echo e(route('student.courses.index')); ?>" class="course-detail-back">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> My Courses
            </a>
        </div>
    </section>

    
    <div class="user-stat-grid course-detail-stat-grid">
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Modules','value' => ''.e($modulesCount).'','icon' => 'fa-layer-group','trend' => ''.e($modulesProgress).'%','footer' => 'Learning content']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Modules','value' => ''.e($modulesCount).'','icon' => 'fa-layer-group','trend' => ''.e($modulesProgress).'%','footer' => 'Learning content']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Assignments','value' => ''.e($assignmentsCount).'','icon' => 'fa-file-pen','trend' => ''.e($assignmentsProgress).'%','footer' => 'Submitted progress']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Assignments','value' => ''.e($assignmentsCount).'','icon' => 'fa-file-pen','trend' => ''.e($assignmentsProgress).'%','footer' => 'Submitted progress']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Quizzes','value' => ''.e($quizzesCount).'','icon' => 'fa-circle-question','trend' => ''.e($quizzesProgress).'%','footer' => 'Attempted progress']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Quizzes','value' => ''.e($quizzesCount).'','icon' => 'fa-circle-question','trend' => ''.e($quizzesProgress).'%','footer' => 'Attempted progress']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Discussions','value' => ''.e($discussionsCount).'','icon' => 'fa-comments','footer' => 'Course community']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Discussions','value' => ''.e($discussionsCount).'','icon' => 'fa-comments','footer' => 'Course community']); ?>
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

    
    <section class="user-panel course-progress-panel" aria-labelledby="course-progress-title">
        <div class="user-panel-head">
            <div>
                <span class="course-section-eyebrow">Keep moving</span>
                <h3 id="course-progress-title"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Your Progress</h3>
            </div>
            <span class="course-progress-total"><?php echo e($overallProgress); ?>% overall</span>
        </div>
        <div class="user-panel-body">
            <div class="course-progress-grid">
                <div class="course-progress-item">
                    <div class="course-progress-label"><span>Modules</span><strong><?php echo e($modulesProgress); ?>%</strong></div>
                    <div class="learning-progress" role="progressbar" aria-label="Modules progress" aria-valuenow="<?php echo e($modulesProgress); ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?php echo e($modulesProgress); ?>%"></span></div>
                    <small>Work through the learning content</small>
                </div>
                <div class="course-progress-item course-progress-item-amber">
                    <div class="course-progress-label"><span>Assignments</span><strong><?php echo e($assignmentsProgress); ?>%</strong></div>
                    <div class="learning-progress" role="progressbar" aria-label="Assignments progress" aria-valuenow="<?php echo e($assignmentsProgress); ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?php echo e($assignmentsProgress); ?>%"></span></div>
                    <small>Submit your course tasks</small>
                </div>
                <div class="course-progress-item course-progress-item-violet">
                    <div class="course-progress-label"><span>Quizzes</span><strong><?php echo e($quizzesProgress); ?>%</strong></div>
                    <div class="learning-progress" role="progressbar" aria-label="Quizzes progress" aria-valuenow="<?php echo e($quizzesProgress); ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?php echo e($quizzesProgress); ?>%"></span></div>
                    <small>Check your understanding</small>
                </div>
            </div>
        </div>
    </section>

    
    <div class="course-content-heading">
        <div>
            <span class="course-section-eyebrow">Learning paths</span>
            <h2>Continue your course</h2>
        </div>
        <span class="course-content-heading-note">Choose what to work on next</span>
    </div>

    <div class="course-content-grid">
        <a href="<?php echo e(route('student.courses.modules.index', $course)); ?>" class="course-content-card course-content-card-green" aria-label="Open modules, <?php echo e($modulesProgress); ?> percent complete">
            <span class="course-content-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
            <span class="course-content-copy"><span class="course-content-label">Content</span><strong>Modules</strong><small><?php echo e($modulesCount); ?> modules to work through.</small></span>
            <span class="course-content-footer"><span><?php echo e($modulesProgress); ?>% done</span><span class="course-content-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></span>
        </a>

        <a href="<?php echo e(route('student.courses.assignments.index', $course)); ?>" class="course-content-card course-content-card-amber" aria-label="Open assignments, <?php echo e($assignmentsProgress); ?> percent submitted">
            <span class="course-content-icon"><i class="fa-solid fa-file-pen" aria-hidden="true"></i></span>
            <span class="course-content-copy"><span class="course-content-label">Tasks</span><strong>Assignments</strong><small><?php echo e($assignmentsCount); ?> assignments with due dates.</small></span>
            <span class="course-content-footer"><span><?php echo e($assignmentsProgress); ?>% submitted</span><span class="course-content-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></span>
        </a>

        <a href="<?php echo e(route('student.courses.quizzes.index', $course)); ?>" class="course-content-card course-content-card-violet" aria-label="Open quizzes, <?php echo e($quizzesProgress); ?> percent attempted">
            <span class="course-content-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
            <span class="course-content-copy"><span class="course-content-label">Assessment</span><strong>Quizzes</strong><small><?php echo e($quizzesCount); ?> quizzes to test your knowledge.</small></span>
            <span class="course-content-footer"><span><?php echo e($quizzesProgress); ?>% attempted</span><span class="course-content-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></span>
        </a>

        <a href="<?php echo e(route('student.courses.discussions.index', $course)); ?>" class="course-content-card course-content-card-rose" aria-label="Open discussions, <?php echo e($discussionsCount); ?> conversations">
            <span class="course-content-icon"><i class="fa-solid fa-comments" aria-hidden="true"></i></span>
            <span class="course-content-copy"><span class="course-content-label">Community</span><strong>Discussions</strong><small><?php echo e($discussionsCount); ?> conversations to join.</small></span>
            <span class="course-content-footer"><span>Ask and share</span><span class="course-content-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></span>
        </a>
    </div>

    
    <section class="user-panel course-announcements-panel" aria-labelledby="course-announcements-title">
        <div class="user-panel-head">
            <div>
                <span class="course-section-eyebrow">Stay up to date</span>
                <h3 id="course-announcements-title"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Recent Announcements</h3>
            </div>
            <a href="<?php echo e(route('student.courses.announcements.index', $course)); ?>" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="user-panel-body">
            <?php $__empty_1 = true; $__currentLoopData = $recentAnnouncements ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ann): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $announcementDate = $ann->publish_at ?? $ann->created_at; ?>
                <a href="<?php echo e(route('student.courses.announcements.show', [$course, $ann])); ?>" class="course-announcement-row">
                    <span class="course-announcement-icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span>
                    <span class="course-announcement-copy"><strong><?php echo e($ann->title); ?></strong><small><?php echo e(optional($announcementDate)->format('M d, Y')); ?></small></span>
                    <?php if(($ann->unread ?? false)): ?>
                        <span class="user-status pending">New</span>
                    <?php else: ?>
                        <span class="course-announcement-read">Read</span>
                    <?php endif; ?>
                    <i class="fa-solid fa-chevron-right course-announcement-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-bullhorn','title' => 'No recent announcements','description' => 'Announcements for this course will appear here.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-bullhorn','title' => 'No recent announcements','description' => 'Announcements for this course will appear here.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $attributes = $__attributesOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $component = $__componentOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__componentOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php $__env->startPush('styles'); ?>
<style>
    .course-detail-page { --course-green:#059669; --course-green-soft:rgba(16,185,129,.12); --course-amber:#d97706; --course-amber-soft:rgba(245,158,11,.13); --course-violet:#7c3aed; --course-violet-soft:rgba(124,58,237,.12); --course-rose:#db2777; --course-rose-soft:rgba(219,39,119,.11); }
    .course-detail-hero { position:relative; display:flex; justify-content:space-between; gap:24px; overflow:hidden; padding:28px; border:1px solid rgba(98,201,245,.22); border-radius:20px; background:linear-gradient(135deg,rgba(16,43,103,.98),rgba(20,67,104,.96) 56%,rgba(4,120,87,.90)); box-shadow:0 22px 52px rgba(3,8,20,.26); color:#fff; }
    .course-detail-hero::after { content:""; position:absolute; right:-8%; bottom:-70%; width:46%; height:220%; border-radius:50%; background:radial-gradient(circle,rgba(110,231,183,.22),transparent 65%); pointer-events:none; }
    .course-detail-hero-main { position:relative; z-index:1; min-width:0; }
    .course-detail-identity { display:flex; align-items:center; gap:11px; margin-bottom:18px; }
    .course-detail-mark { display:grid; place-items:center; width:42px; height:42px; border:1px solid rgba(255,255,255,.22); border-radius:13px; color:#6ee7b7; background:rgba(255,255,255,.10); font-size:1.05rem; }
    .course-detail-kicker,.course-detail-code { display:block; text-transform:uppercase; letter-spacing:.12em; font-size:.64rem; font-weight:800; }
    .course-detail-kicker { color:#a7f3d0; }
    .course-detail-code { margin-top:3px; color:rgba(255,255,255,.62); }
    .course-detail-hero h1 { max-width:760px; margin:0; color:#fff; font-size:clamp(1.65rem,3vw,2.7rem); line-height:1.06; letter-spacing:-.045em; }
    .course-detail-hero p { max-width:700px; margin:11px 0 0; color:rgba(238,244,255,.80); font-size:.88rem; line-height:1.65; }
    .course-detail-meta { display:flex; flex-wrap:wrap; gap:9px 16px; margin-top:18px; color:rgba(219,234,254,.88); font-size:.72rem; }
    .course-detail-meta i { margin-right:5px; color:#a7f3d0; }
    .course-detail-hero-side { position:relative; z-index:1; display:flex; flex:0 0 auto; flex-direction:column; align-items:center; justify-content:center; gap:14px; min-width:150px; }
    .course-progress-orbit { display:grid; place-items:center; width:132px; height:132px; border-radius:50%; background:conic-gradient(#6ee7b7 var(--course-progress),rgba(255,255,255,.16) 0); box-shadow:0 0 0 1px rgba(255,255,255,.16),0 14px 28px rgba(3,8,20,.18); }
    .course-progress-orbit-inner { display:grid; place-items:center; align-content:center; width:102px; height:102px; border-radius:50%; background:#123465; text-align:center; }
    .course-progress-orbit-inner strong { color:#fff; font-size:1.35rem; letter-spacing:-.04em; }
    .course-progress-orbit-inner span { margin-top:3px; color:rgba(238,244,255,.66); font-size:.58rem; text-transform:uppercase; letter-spacing:.08em; }
    .course-detail-back { color:rgba(255,255,255,.86); font-size:.72rem; font-weight:750; text-decoration:none; }
    .course-detail-back:hover,.course-detail-back:focus-visible { color:#fff; text-decoration:underline; }
    .course-detail-stat-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .course-section-eyebrow { display:block; margin-bottom:3px; color:var(--user-accent-strong); font-size:.62rem; font-weight:850; letter-spacing:.12em; text-transform:uppercase; }
    .course-progress-total { color:var(--user-accent-strong); font-size:.72rem; font-weight:850; }
    .course-progress-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:15px; }
    .course-progress-item { padding:14px; border:1px solid rgba(5,150,105,.18); border-radius:13px; background:linear-gradient(145deg,rgba(16,185,129,.08),transparent); }
    .course-progress-item-amber { border-color:rgba(217,119,6,.18); background:linear-gradient(145deg,rgba(245,158,11,.08),transparent); }
    .course-progress-item-violet { border-color:rgba(124,58,237,.18); background:linear-gradient(145deg,rgba(124,58,237,.08),transparent); }
    .course-progress-label { display:flex; justify-content:space-between; gap:10px; margin-bottom:10px; color:var(--dash-text); font-size:.75rem; font-weight:800; }
    .course-progress-label strong { color:var(--user-accent-strong); }
    .course-progress-item small { display:block; margin-top:8px; color:var(--dash-muted); font-size:.68rem; }
    .course-content-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-top:2px; }
    .course-content-heading h2 { margin:0; color:var(--dash-text); font-size:1.05rem; letter-spacing:-.02em; }
    .course-content-heading-note { color:var(--dash-muted); font-size:.7rem; }
    .course-content-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .course-content-card { display:flex; min-width:0; flex-direction:column; gap:13px; padding:16px; border:1px solid var(--dash-line); border-radius:16px; background:var(--dash-surface); box-shadow:0 10px 24px rgba(3,8,20,.10); color:var(--dash-text); text-decoration:none; transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease; }
    .course-content-card:hover { transform:translateY(-3px); border-color:var(--user-accent); box-shadow:0 16px 32px rgba(3,8,20,.16); }
    .course-content-card:focus-visible { outline:3px solid var(--user-accent); outline-offset:3px; }
    .course-content-card-green { --card-accent:var(--course-green); --card-soft:var(--course-green-soft); }
    .course-content-card-amber { --card-accent:var(--course-amber); --card-soft:var(--course-amber-soft); }
    .course-content-card-violet { --card-accent:var(--course-violet); --card-soft:var(--course-violet-soft); }
    .course-content-card-rose { --card-accent:var(--course-rose); --card-soft:var(--course-rose-soft); }
    .course-content-icon { display:grid; place-items:center; width:38px; height:38px; border-radius:12px; color:var(--card-accent); background:var(--card-soft); }
    .course-content-copy { display:flex; min-width:0; flex:1; flex-direction:column; }
    .course-content-label { color:var(--card-accent); font-size:.61rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
    .course-content-copy strong { margin-top:4px; font-size:.92rem; }
    .course-content-copy small { min-height:2.6em; margin-top:7px; color:var(--dash-muted); font-size:.70rem; line-height:1.45; }
    .course-content-footer { display:flex; align-items:center; justify-content:space-between; gap:10px; color:var(--dash-muted); font-size:.68rem; }
    .course-content-arrow { display:grid; place-items:center; width:26px; height:26px; border-radius:8px; color:var(--card-accent); background:var(--card-soft); }
    .course-announcements-panel .user-panel-body { padding:10px 15px 15px; }
    .course-announcement-row { display:flex; align-items:center; gap:11px; min-width:0; padding:12px 7px; border-bottom:1px solid var(--dash-line); color:var(--dash-text); text-decoration:none; }
    .course-announcement-row:last-child { border-bottom:0; }
    .course-announcement-row:hover { background:rgba(16,185,129,.045); }
    .course-announcement-row:focus-visible { outline:3px solid var(--user-accent); outline-offset:2px; border-radius:9px; }
    .course-announcement-icon { display:grid; flex:0 0 auto; place-items:center; width:32px; height:32px; border-radius:10px; color:var(--user-accent-strong); background:var(--user-accent-soft); font-size:.75rem; }
    .course-announcement-copy { display:flex; min-width:0; flex:1; flex-direction:column; gap:3px; }
    .course-announcement-copy strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.77rem; }
    .course-announcement-copy small,.course-announcement-read { color:var(--dash-muted); font-size:.65rem; }
    .course-announcement-arrow { color:var(--dash-muted); font-size:.68rem; }
    @media (max-width:1100px) { .course-detail-stat-grid,.course-content-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:760px) { .course-detail-hero { flex-direction:column; padding:21px; } .course-detail-hero-side { flex-direction:row; justify-content:space-between; width:100%; } .course-progress-grid { grid-template-columns:1fr; } .course-content-heading { align-items:flex-start; flex-direction:column; } }
    @media (max-width:480px) { .course-detail-hero h1 { font-size:1.55rem; } .course-detail-hero-side { align-items:flex-start; } .course-progress-orbit { width:106px; height:106px; } .course-progress-orbit-inner { width:82px; height:82px; } .course-progress-orbit-inner strong { font-size:1.1rem; } .course-detail-stat-grid,.course-content-grid { grid-template-columns:1fr; } .course-announcement-row { align-items:flex-start; flex-wrap:wrap; } .course-announcement-copy { flex-basis:calc(100% - 45px); } }
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\courses\show.blade.php ENDPATH**/ ?>