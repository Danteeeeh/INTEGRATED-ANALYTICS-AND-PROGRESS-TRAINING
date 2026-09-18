<?php $__env->startSection('title', 'Academic Period Details'); ?>
<?php
    $activeNav = 'academic_periods';
    $pageTitle = 'Academic Period Details';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            <?php echo e($academicPeriod->name); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Academic Period Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Code:</span><span><?php echo e($academicPeriod->code); ?></span></div>
        <div class="modal-row"><span>Dates:</span><span><?php echo e($academicPeriod->start_date->format('M d, Y')); ?> - <?php echo e($academicPeriod->end_date->format('M d, Y')); ?></span></div>
        <div class="modal-row"><span>Status:</span><span>
            <div style="display: flex; gap: 8px;">
                <?php if($academicPeriod->is_current): ?>
                    <span class="badge-active">Current</span>
                <?php endif; ?>
                <?php if($academicPeriod->is_enrollment_open): ?>
                    <span style="background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;">Enrollment Open</span>
                <?php endif; ?>
            </div>
        </span></div>
        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description</div>
        <div class="modal-row"><span></span><span><?php echo e($academicPeriod->description ?? 'No description'); ?></span></div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Classes (<?php echo e($academicPeriod->classes->count()); ?>)</h3>
            <a href="<?php echo e(route('admin.classes.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Class
            </a>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($academicPeriod->classes->count() > 0): ?>
                    <?php $__currentLoopData = $academicPeriod->classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($class->code); ?></td>
                            <td><?php echo e($class->name); ?></td>
                            <td><?php echo e($class->course->code); ?> - <?php echo e($class->course->name); ?></td>
                            <td><?php echo e($class->instructor->full_name); ?></td>
                            <td class="actions-cell">
                                <a href="<?php echo e(route('admin.classes.show', $class)); ?>" class="btn-icon btn-view" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No classes found for this period. <a href="<?php echo e(route('admin.classes.create')); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('admin.academic_periods.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Academic Periods
        </a>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\academic_periods\show.blade.php ENDPATH**/ ?>