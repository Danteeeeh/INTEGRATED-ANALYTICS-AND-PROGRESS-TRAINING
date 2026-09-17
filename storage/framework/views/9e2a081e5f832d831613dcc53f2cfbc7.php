<?php $__env->startSection('title', $calendarEvent->title); ?>
<?php $activeNav = 'calendar'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($calendarEvent->title).'','subtitle' => 'Calendar event details.','icon' => 'fa-calendar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($calendarEvent->title).'','subtitle' => 'Calendar event details.','icon' => 'fa-calendar']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="user-status"><?php echo e(ucfirst(str_replace('_', ' ', $calendarEvent->event_type))); ?></span>
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($calendarEvent->visibility).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($calendarEvent->visibility).'']); ?>
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
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.calendar.edit', $calendarEvent)); ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="<?php echo e(route('admin.calendar.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-info-circle"></i> Event Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Starts</label>
                    <div><?php echo e($calendarEvent->start_at?->format('l, F j, Y g:i A')); ?></div>
                </div>
                <div class="form-field">
                    <label>Ends</label>
                    <div><?php echo e($calendarEvent->is_all_day ? 'All day' : ($calendarEvent->end_at?->format('l, F j, Y g:i A') ?? '—')); ?></div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div><?php echo e($calendarEvent->course?->title ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div><?php echo e($calendarEvent->class?->code ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Location</label>
                    <div><?php echo e($calendarEvent->location ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div><?php echo e($calendarEvent->creator?->name ?? '—'); ?></div>
                </div>
                <?php if($calendarEvent->description): ?>
                    <div class="form-field full">
                        <label>Description</label>
                        <div style="white-space:pre-line"><?php echo e($calendarEvent->description); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-actions">
        <form action="<?php echo e(route('admin.calendar.destroy', $calendarEvent)); ?>" method="POST" onsubmit="return confirm('Delete this event?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\calendar\show.blade.php ENDPATH**/ ?>