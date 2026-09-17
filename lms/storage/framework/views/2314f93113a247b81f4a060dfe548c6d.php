<?php $__env->startSection('title', 'My Courses'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'My Courses';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'My Courses','subtitle' => 'Manage the courses assigned to you.','icon' => 'fa-book']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'My Courses','subtitle' => 'Manage the courses assigned to you.','icon' => 'fa-book']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.classes.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-school"></i> Manage Classes
            </a>
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

    <form class="user-toolbar" method="GET" action="<?php echo e(route('instructor.courses.index')); ?>" style="margin-bottom:16px">
        <select class="form-control" name="status" aria-label="Filter course status">
            <option value="">All Status</option>
            <?php $__currentLoopData = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('instructor.courses.index')); ?>">Clear</a>
    </form>

    <?php
        $feature = request('feature');
        $featureMeta = [
            'assignments' => ['label' => 'Assignments', 'icon' => 'fa-tasks', 'hint' => 'Manage tasks and submissions', 'route' => 'instructor.courses.assignments.index', 'color' => 'amber'],
            'quizzes' => ['label' => 'Quizzes', 'icon' => 'fa-question-circle', 'hint' => 'Manage quizzes and attempts', 'route' => 'instructor.courses.quizzes.index', 'color' => 'violet'],
            'discussions' => ['label' => 'Discussions', 'icon' => 'fa-comments', 'hint' => 'Open course conversations', 'route' => 'instructor.courses.discussions.index', 'color' => 'cyan'],
            'announcements' => ['label' => 'Announcements', 'icon' => 'fa-bullhorn', 'hint' => 'Post course updates', 'route' => 'instructor.courses.announcements.index', 'color' => 'rose'],
        ];
    ?>
    <?php if($feature && isset($featureMeta[$feature])): ?>
        <section class="feature-picker">
            <div class="feature-picker-icon fp-<?php echo e($featureMeta[$feature]['color']); ?>"><i class="fa-solid <?php echo e($featureMeta[$feature]['icon']); ?>"></i></div>
            <div class="feature-picker-copy">
                <span class="feature-picker-kicker">Course feature</span>
                <h3>Choose a course for <?php echo e($featureMeta[$feature]['label']); ?></h3>
                <p><?php echo e($featureMeta[$feature]['hint']); ?>. Piliin ang course na gusto mong buksan.</p>
            </div>
            <a href="<?php echo e(route('instructor.courses.index')); ?>" class="feature-picker-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        </section>
        <div class="course-choice-grid">
            <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route($featureMeta[$feature]['route'], $course)); ?>" class="course-choice-card">
                    <span class="choice-icon fp-<?php echo e($featureMeta[$feature]['color']); ?>"><i class="fa-solid <?php echo e($featureMeta[$feature]['icon']); ?>"></i></span>
                    <span class="choice-copy"><strong><?php echo e($course->code); ?></strong><small><?php echo e($course->name ?? $course->title); ?></small></span>
                    <i class="fa-solid fa-arrow-right choice-arrow"></i>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="feature-empty">Wala pang course na naka-assign sa iyo.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if($courses->count() > 0): ?>
        <div class="learning-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="learning-card">
                    <div>
                        <div class="user-kicker"><i class="fa-solid fa-book" aria-hidden="true"></i> <?php echo e($course->code); ?></div>
                        <h3><?php echo e($course->name ?? $course->title); ?></h3>
                        <p><?php echo e($course->description ?? 'No description provided.'); ?></p>
                    </div>
                    <div>
                        <div class="user-actions" style="margin-top:12px">
                            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e(($course->is_active ?? true) ? 'active' : 'inactive').'','label' => ''.e(($course->is_active ?? true) ? 'Active' : 'Inactive').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e(($course->is_active ?? true) ? 'active' : 'inactive').'','label' => ''.e(($course->is_active ?? true) ? 'Active' : 'Inactive').'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
                        </div>
                        <div class="user-actions" style="margin-top:8px">
                            <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                            <a href="<?php echo e(route('instructor.courses.edit', $course)); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-book','title' => 'No courses assigned','description' => 'Wala pang course na naka-assign sa iyo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-book','title' => 'No courses assigned','description' => 'Wala pang course na naka-assign sa iyo.']); ?>
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

    <style>
        .feature-picker{display:flex;align-items:center;gap:14px;margin-bottom:16px;padding:16px 18px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:14px;background:linear-gradient(135deg,rgba(36,73,198,.22),rgba(10,16,32,.72));}
        .feature-picker-icon,.choice-icon{display:grid;place-items:center;flex:none;border-radius:11px;width:42px;height:42px;font-size:16px}.feature-picker-copy{flex:1;min-width:0}.feature-picker-kicker{display:block;color:var(--bcp-cyan-400,#62c9f5);font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.feature-picker-copy h3{margin:3px 0;color:var(--bcp-ink,#eef4ff);font-size:.95rem}.feature-picker-copy p{margin:0;color:var(--bcp-muted,#98a7c4);font-size:.75rem}.feature-picker-clear{color:var(--bcp-muted,#98a7c4);font-size:.72rem;text-decoration:none;white-space:nowrap}.course-choice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:18px}.course-choice-card{display:flex;align-items:center;gap:11px;padding:14px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:12px;background:var(--bcp-card,var(--dash-surface,#151c2c));text-decoration:none;transition:transform .15s,border-color .15s}.course-choice-card:hover{transform:translateY(-2px);border-color:rgba(98,201,245,.45)}.choice-copy{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}.choice-copy strong{color:var(--bcp-ink,#eef4ff);font-size:.82rem}.choice-copy small{color:var(--bcp-muted,#98a7c4);font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.choice-arrow{color:var(--bcp-cyan-400,#62c9f5);font-size:.75rem}.feature-empty{grid-column:1/-1;text-align:center;color:var(--bcp-muted,#98a7c4);padding:24px}.fp-amber{background:rgba(251,191,36,.14);color:#fcd34d}.fp-violet{background:rgba(139,92,246,.15);color:#c4b5fd}.fp-cyan{background:rgba(34,211,238,.14);color:#67e8f9}.fp-rose{background:rgba(244,63,94,.14);color:#fda4af}@media(max-width:640px){.feature-picker{align-items:flex-start}.feature-picker-clear{margin-left:auto}.course-choice-grid{grid-template-columns:1fr}}
    </style>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\index.blade.php ENDPATH**/ ?>