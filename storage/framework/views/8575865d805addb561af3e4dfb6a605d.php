<?php $__env->startSection('title', 'Classes'); ?>
<?php $activeNav = 'classes'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Classes','subtitle' => 'Browse available classes and manage your enrollments.','icon' => 'fa-school']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Classes','subtitle' => 'Browse available classes and manage your enrollments.','icon' => 'fa-school']); ?>
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

    <?php if($enrolledClasses->count() > 0): ?>
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-user-graduate"></i> My Enrolled Classes</h3>
                <span class="user-status active"><?php echo e($enrolledClasses->count()); ?> enrolled</span>
            </div>
            <div class="user-panel-body">
                <div class="learning-grid">
                    <?php $__currentLoopData = $enrolledClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker"><?php echo e($class->code); ?> · <?php echo e($class->academicPeriod?->name ?? ''); ?></div>
                                <h3><?php echo e($class->course?->title ?? $class->name); ?></h3>
                                <p><?php echo e($class->instructor?->full_name ?? '—'); ?> · <?php echo e($class->schedule ?? ''); ?></p>
                            </div>
                            <div class="user-actions" style="justify-content:space-between">
                                <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => 'active','label' => 'Enrolled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'active','label' => 'Enrolled']); ?>
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
                                <a href="<?php echo e(route('student.classes.show', $class)); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Open</a>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($availableClasses->count() > 0): ?>
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-book-open"></i> Available Classes</h3>
                <span class="user-status"><?php echo e($availableClasses->count()); ?> open</span>
            </div>
            <div class="user-panel-body">
                <div class="learning-grid">
                    <?php $__currentLoopData = $availableClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker"><?php echo e($class->code); ?> · <?php echo e($class->academicPeriod?->name ?? ''); ?></div>
                                <h3><?php echo e($class->course?->title ?? $class->name); ?></h3>
                                <p><?php echo e($class->instructor?->full_name ?? '—'); ?> · <?php echo e($class->schedule ?? ''); ?></p>
                            </div>
                            <div class="user-actions" style="justify-content:space-between">
                                <span class="user-status active">Seats open</span>
                                <a href="<?php echo e(route('student.classes.show', $class)); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($enrolledClasses->count() === 0 && $availableClasses->count() === 0): ?>
        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-school','title' => 'No classes available','description' => 'There are no open classes to enroll in right now.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-school','title' => 'No classes available','description' => 'There are no open classes to enroll in right now.']); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\classes\index.blade.php ENDPATH**/ ?>