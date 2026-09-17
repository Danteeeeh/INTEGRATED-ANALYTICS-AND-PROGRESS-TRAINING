<?php $__env->startSection('title', $lesson->title); ?>
<?php $activeNav = 'lessons'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($lesson->title).'','subtitle' => ''.e($course->title).' · '.e($module->title).'','icon' => 'fa-book-open']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($lesson->title).'','subtitle' => ''.e($course->title).' · '.e($module->title).'','icon' => 'fa-book-open']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($progress->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($progress->status).'']); ?>
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
            <span class="user-status"><?php echo e(round($progress->progress_percent ?? 0)); ?>% complete</span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.modules.show', [$course, $module])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Module</a>
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

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book-open"></i> Lesson Content</h3>
            <?php if($lesson->duration_minutes): ?>
                <span class="user-email"><?php echo e($lesson->duration_minutes); ?> min</span>
            <?php endif; ?>
        </div>
        <div class="user-panel-body">
            <?php if($lesson->description): ?>
                <div style="margin-bottom:12px;color:var(--dash-muted)"><?php echo e($lesson->description); ?></div>
            <?php endif; ?>
            <?php if($lesson->content): ?>
                <div style="white-space:pre-line"><?php echo e($lesson->content); ?></div>
            <?php else: ?>
                <p class="user-email">This lesson has no text content.</p>
            <?php endif; ?>
            <?php if($lesson->external_url): ?>
                <div class="user-actions" style="justify-content:flex-start;margin-top:14px">
                    <a href="<?php echo e($lesson->external_url); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="fa-solid fa-up-right-from-square"></i> Open External Lesson</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-paperclip"></i> Materials</h3>
            <span class="user-status"><?php echo e($lesson->materials->count()); ?> materials</span>
        </div>
        <div class="user-panel-body">
            <?php if($lesson->materials->count() > 0): ?>
                <div class="user-actions" style="justify-content:flex-start;flex-wrap:wrap">
                    <?php $__currentLoopData = $lesson->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($material->url ?? $material->path ?? '#'); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-file"></i> <?php echo e($material->title ?? $material->name ?? $material->file_name ?? 'Material'); ?>

                            <?php if($material->is_required): ?><span class="user-status active">Required</span><?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-paperclip','title' => 'No materials','description' => 'No materials have been attached to this lesson.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-paperclip','title' => 'No materials','description' => 'No materials have been attached to this lesson.']); ?>
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

    <?php if($lesson->content): ?>
        <form action="<?php echo e(route('student.courses.modules.lessons.complete', [$course, $module, $lesson])); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="learning-next-action">
                <div>
                    <strong><?php echo e($progress->status === 'completed' ? 'Completed — you can review anytime' : 'Finished this lesson?'); ?></strong>
                    <span>Mark it complete to update your progress.</span>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid <?php echo e($progress->status === 'completed' ? 'fa-circle-check' : 'fa-check-circle'); ?>"></i>
                    <?php echo e($progress->status === 'completed' ? 'Completed' : 'Mark Complete'); ?>

                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\lessons\show.blade.php ENDPATH**/ ?>