<?php $__env->startSection('title', $announcement->title); ?>
<?php $activeNav = 'announcements'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($announcement->title).'','subtitle' => ''.e($course->title).' — announcement','icon' => 'fa-bullhorn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($announcement->title).'','subtitle' => ''.e($course->title).' — announcement','icon' => 'fa-bullhorn']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if($announcement->is_pinned): ?><span class="user-status active">Pinned</span><?php endif; ?>
            <span><?php echo e($announcement->created_at?->format('l, F j, Y')); ?></span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.announcements.index', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-bullhorn"></i> Announcement</h3>
            <span class="user-email"><?php echo e($announcement->creator?->name ?? ''); ?></span>
        </div>
        <div class="user-panel-body">
            <div style="white-space:pre-line"><?php echo e($announcement->body); ?></div>

            <?php if($announcement->attachment): ?>
                <div class="user-actions" style="justify-content:flex-start;margin-top:14px">
                    <a href="<?php echo e($announcement->attachment->url ?? '#'); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-paperclip"></i> Attachment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\announcements\show.blade.php ENDPATH**/ ?>