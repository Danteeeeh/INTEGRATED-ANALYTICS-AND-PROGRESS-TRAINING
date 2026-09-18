<?php $__env->startSection('title', $module->title); ?>
<?php $activeNav = 'modules'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($module->title).'','subtitle' => ''.e($course->title).' — module lessons','icon' => 'fa-cubes']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($module->title).'','subtitle' => ''.e($course->title).' — module lessons','icon' => 'fa-cubes']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="user-status"><?php echo e($module->lessons->count()); ?> lessons</span>
            <?php if($module->is_required): ?><span class="user-status active">Required</span><?php endif; ?>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.modules.index', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

    <?php if($module->description): ?>
        <div class="user-panel">
            <div class="user-panel-body">
                <p style="margin:0;color:var(--dash-muted)"><?php echo e($module->description); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if($module->lessons->count() > 0): ?>
        <div class="learning-grid">
            <?php $__currentLoopData = $module->lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $p = $lesson->progress->first();
                    $completed = $p && $p->status === 'completed';
                ?>
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            Lesson <?php echo e($lesson->position ?? $loop->iteration); ?>

                            <?php if($completed): ?><span style="color:var(--user-success)"> · Completed</span><?php endif; ?>
                        </div>
                        <h3><?php echo e($lesson->title); ?></h3>
                        <p><?php echo e(Str::limit($lesson->description ?? '', 80)); ?></p>
                    </div>
                    <div>
                        <?php if($p): ?>
                            <div class="learning-progress"><span style="width: <?php echo e(min(100, round($p->progress_percent ?? 0))); ?>%"></span></div>
                        <?php endif; ?>
                        <div class="user-actions" style="justify-content:flex-end;margin-top:10px">
                            <a href="<?php echo e(route('student.courses.modules.lessons.show', [$course, $module, $lesson])); ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid <?php echo e($completed ? 'fa-circle-check' : 'fa-play'); ?>"></i> <?php echo e($completed ? 'Review' : 'Start'); ?>

                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-cubes','title' => 'No lessons','description' => 'No published lessons in this module yet.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-cubes','title' => 'No lessons','description' => 'No published lessons in this module yet.']); ?>
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

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\modules\show.blade.php ENDPATH**/ ?>