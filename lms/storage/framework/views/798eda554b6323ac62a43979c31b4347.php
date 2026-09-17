<?php $__env->startSection('title', 'My Classes'); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = 'My Classes';
    $pageIcon = '<i class="fa-solid fa-school"></i>';
    $feature = request('feature');
    $featureMeta = [
        'gradebook' => ['label' => 'Gradebook', 'icon' => 'fa-graduation-cap', 'hint' => 'Enter grades and review student performance', 'route' => 'instructor.classes.gradebook.index', 'color' => 'violet'],
        'attendance' => ['label' => 'Attendance', 'icon' => 'fa-clipboard-user', 'hint' => 'Record and review attendance', 'route' => 'instructor.classes.attendance.index', 'color' => 'green'],
        'virtual_classes' => ['label' => 'Virtual Classes', 'icon' => 'fa-video', 'hint' => 'Manage online class sessions', 'route' => 'instructor.classes.virtual_classes.index', 'color' => 'cyan'],
        'calendar' => ['label' => 'Calendar', 'icon' => 'fa-calendar', 'hint' => 'View class events and schedule', 'route' => 'instructor.classes.calendar.index', 'color' => 'amber'],
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'My Classes','subtitle' => 'Manage the classes assigned to you.','icon' => 'fa-school']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'My Classes','subtitle' => 'Manage the classes assigned to you.','icon' => 'fa-school']); ?>
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

    <form class="user-toolbar" method="GET" action="<?php echo e(route('instructor.classes.index')); ?>" style="margin-bottom:16px">
        <select class="form-control" name="status" aria-label="Filter class status">
            <option value="">All Status</option>
            <?php $__currentLoopData = ['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('instructor.classes.index')); ?>">Clear</a>
    </form>

    <?php if($feature && isset($featureMeta[$feature])): ?>
        <section class="feature-picker">
            <div class="feature-picker-icon fp-<?php echo e($featureMeta[$feature]['color']); ?>"><i class="fa-solid <?php echo e($featureMeta[$feature]['icon']); ?>"></i></div>
            <div class="feature-picker-copy"><span class="feature-picker-kicker">Class feature</span><h3>Choose a class for <?php echo e($featureMeta[$feature]['label']); ?></h3><p><?php echo e($featureMeta[$feature]['hint']); ?>. Piliin ang class na gusto mong buksan.</p></div>
            <a href="<?php echo e(route('instructor.classes.index')); ?>" class="feature-picker-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        </section>
        <div class="class-choice-grid">
            <?php $__empty_1 = true; $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route($featureMeta[$feature]['route'], $class)); ?>" class="class-choice-card">
                    <span class="choice-icon fp-<?php echo e($featureMeta[$feature]['color']); ?>"><i class="fa-solid <?php echo e($featureMeta[$feature]['icon']); ?>"></i></span>
                    <span class="choice-copy"><strong><?php echo e($class->code); ?></strong><small><?php echo e($class->course->name ?? $class->course->title ?? 'Class'); ?> · <?php echo e($class->schedule ?? 'No schedule'); ?></small></span>
                    <i class="fa-solid fa-arrow-right choice-arrow"></i>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="feature-empty">Wala pang class na naka-assign sa iyo.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-school"></i> My Classes</h3></div>
        <div class="user-panel-body">
            <?php if($classes->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($class->code); ?></strong></td>
                                    <td><?php echo e($class->name); ?></td>
                                    <td><?php echo e($class->course->name ?? $class->course->title ?? '-'); ?></td>
                                    <td>
                                        <?php echo e($class->enrolled_count ?? $class->enrollments_count ?? 0); ?> / <?php echo e($class->max_students); ?>

                                        <?php if(($class->enrolled_count ?? 0) >= ($class->max_students ?? 0)): ?>
                                            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => 'inactive','label' => 'Full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'inactive','label' => 'Full']); ?>
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
                                        <?php else: ?>
                                            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => 'active','label' => 'Available']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'active','label' => 'Available']); ?>
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
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="<?php echo e(route('instructor.classes.show', $class)); ?>" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo e(route('instructor.classes.gradebook.index', $class)); ?>" class="btn btn-icon" title="Gradebook"><i class="fa-solid fa-graduation-cap"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-school','title' => 'No classes assigned','description' => 'Wala pang class na naka-assign sa iyo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-school','title' => 'No classes assigned','description' => 'Wala pang class na naka-assign sa iyo.']); ?>
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
    </div>

    <style>
        .feature-picker{display:flex;align-items:center;gap:14px;margin-bottom:16px;padding:16px 18px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:14px;background:linear-gradient(135deg,rgba(36,73,198,.22),rgba(10,16,32,.72))}.feature-picker-icon,.choice-icon{display:grid;place-items:center;flex:none;border-radius:11px;width:42px;height:42px;font-size:16px}.feature-picker-copy{flex:1;min-width:0}.feature-picker-kicker{display:block;color:var(--bcp-cyan-400,#62c9f5);font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.feature-picker-copy h3{margin:3px 0;color:var(--bcp-ink,#eef4ff);font-size:.95rem}.feature-picker-copy p{margin:0;color:var(--bcp-muted,#98a7c4);font-size:.75rem}.feature-picker-clear{color:var(--bcp-muted,#98a7c4);font-size:.72rem;text-decoration:none;white-space:nowrap}.class-choice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:18px}.class-choice-card{display:flex;align-items:center;gap:11px;padding:14px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:12px;background:var(--bcp-card,var(--dash-surface,#151c2c));text-decoration:none;transition:transform .15s,border-color .15s}.class-choice-card:hover{transform:translateY(-2px);border-color:rgba(98,201,245,.45)}.choice-copy{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}.choice-copy strong{color:var(--bcp-ink,#eef4ff);font-size:.82rem}.choice-copy small{color:var(--bcp-muted,#98a7c4);font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.choice-arrow{color:var(--bcp-cyan-400,#62c9f5);font-size:.75rem}.feature-empty{grid-column:1/-1;text-align:center;color:var(--bcp-muted,#98a7c4);padding:24px}.fp-violet{background:rgba(139,92,246,.15);color:#c4b5fd}.fp-green{background:rgba(52,211,153,.15);color:#6ee7b7}.fp-cyan{background:rgba(34,211,238,.14);color:#67e8f9}.fp-amber{background:rgba(251,191,36,.14);color:#fcd34d}@media(max-width:640px){.feature-picker{align-items:flex-start}.feature-picker-clear{margin-left:auto}.class-choice-grid{grid-template-columns:1fr}}
    </style>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\classes\index.blade.php ENDPATH**/ ?>