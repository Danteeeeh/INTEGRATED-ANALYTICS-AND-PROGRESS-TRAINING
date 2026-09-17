<?php $__env->startSection('title', 'Attendance Record'); ?>
<?php $activeNav = 'attendance'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Attendance Record','subtitle' => 'Attendance details for a single student session.','icon' => 'fa-clipboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Attendance Record','subtitle' => 'Attendance details for a single student session.','icon' => 'fa-clipboard-user']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($attendanceRecord->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($attendanceRecord->status).'']); ?>
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
            <a href="<?php echo e(route('admin.attendance.edit', $attendanceRecord)); ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="<?php echo e(route('admin.attendance.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-info-circle"></i> Record Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div><?php echo e($attendanceRecord->student?->name ?? '—'); ?></div>
                    <div class="user-email"><?php echo e($attendanceRecord->student?->email); ?></div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div><?php echo e($attendanceRecord->class?->code ?? '—'); ?></div>
                    <div class="user-email"><?php echo e($attendanceRecord->class?->course?->title); ?></div>
                </div>
                <div class="form-field">
                    <label>Date</label>
                    <div><?php echo e($attendanceRecord->attendance_date?->format('l, F j, Y')); ?></div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($attendanceRecord->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($attendanceRecord->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?></div>
                </div>
                <div class="form-field">
                    <label>Session</label>
                    <div><?php echo e($attendanceRecord->session_title ?? $attendanceRecord->virtualClass?->title ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Recorded By</label>
                    <div><?php echo e($attendanceRecord->recordedBy?->name ?? '—'); ?></div>
                </div>
                <?php if($attendanceRecord->joined_at): ?>
                    <div class="form-field">
                        <label>Joined</label>
                        <div><?php echo e($attendanceRecord->joined_at?->format('g:i A')); ?></div>
                    </div>
                <?php endif; ?>
                <?php if($attendanceRecord->left_at): ?>
                    <div class="form-field">
                        <label>Left</label>
                        <div><?php echo e($attendanceRecord->left_at?->format('g:i A')); ?></div>
                    </div>
                <?php endif; ?>
                <?php if($attendanceRecord->duration_minutes !== null): ?>
                    <div class="form-field">
                        <label>Duration</label>
                        <div><?php echo e($attendanceRecord->duration_minutes); ?> min</div>
                    </div>
                <?php endif; ?>
                <?php if($attendanceRecord->notes): ?>
                    <div class="form-field full">
                        <label>Notes</label>
                        <div><?php echo e($attendanceRecord->notes); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-actions">
        <form action="<?php echo e(route('admin.attendance.destroy', $attendanceRecord)); ?>" method="POST" onsubmit="return confirm('Delete this attendance record?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\attendance\show.blade.php ENDPATH**/ ?>